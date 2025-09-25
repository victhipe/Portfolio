<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

checkUserRole('admin'); // Только админ

// Проверка CSRF токена
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . BASE_URL . 'admin/manage_users.php');
     exit;
}

// Получение данных
$user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$username = trim($_POST['username'] ?? ''); // Логин важен при создании
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$address = trim($_POST['address'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

$is_editing = ($user_id !== null);
$allowed_roles = ['customer', 'operator', 'admin'];

// --- Валидация ---
$errors = [];

// Логин (только при создании)
if (!$is_editing) {
    if (empty($username)) { $errors[] = "Имя пользователя (логин) обязательно."; }
    elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) { $errors[] = "Логин содержит недопустимые символы или неверную длину."; }
    else {
        // Проверка уникальности username при создании
        $sql_check_user = "SELECT id FROM users WHERE username = ?";
        $stmt_check_user = $conn->prepare($sql_check_user);
        $stmt_check_user->bind_param("s", $username);
        $stmt_check_user->execute();
        if ($stmt_check_user->get_result()->num_rows > 0) {
            $errors[] = "Это имя пользователя уже занято.";
        }
        $stmt_check_user->close();
    }
}

// Email
if (empty($email)) { $errors[] = "Email обязателен."; }
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Неверный формат Email."; }
else {
    // Проверка уникальности email (и для создания, и для редактирования)
    $sql_check_email = "SELECT id FROM users WHERE email = ?" . ($is_editing ? " AND id != ?" : "");
    $stmt_check_email = $conn->prepare($sql_check_email);
    if ($is_editing) {
        $stmt_check_email->bind_param("si", $email, $user_id);
    } else {
        $stmt_check_email->bind_param("s", $email);
    }
    $stmt_check_email->execute();
    if ($stmt_check_email->get_result()->num_rows > 0) {
        $errors[] = "Этот email уже используется другим пользователем.";
    }
    $stmt_check_email->close();
}

// Роль
if (empty($role) || !in_array($role, $allowed_roles)) {
    $errors[] = "Выбрана недопустимая роль пользователя.";
}

// Телефон (необязательный, но если есть - валидируем)
if (!empty($phone_number) && !preg_match('/^\+?[0-9\s\-\(\)]+$/', $phone_number)) {
    $errors[] = "Неверный формат номера телефона.";
}

// Пароль
$hashed_password = null;
if (!$is_editing) { // Пароль обязателен при создании
    if (empty($password)) {
        $errors[] = "Пароль обязателен при создании пользователя.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль должен быть не менее 6 символов.";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Пароли не совпадают.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    }
} elseif (!empty($password)) { // Если пароль введен при редактировании
     if (strlen($password) < 6) {
        $errors[] = "Новый пароль должен быть не менее 6 символов.";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Новые пароли не совпадают.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    }
}

// --- Обработка ошибок валидации ---
if (!empty($errors)) {
    $_SESSION['form_data'] = $_POST; // Сохраняем введенные данные
    unset($_SESSION['form_data']['password'], $_SESSION['form_data']['password_confirm']); // Не сохраняем пароли
    setFlashMessage('error', implode('<br>', $errors));
    $redirect_url = $is_editing ? BASE_URL . 'admin/add_edit_user.php?id=' . $user_id : BASE_URL . 'admin/add_edit_user.php';
    header('Location: ' . $redirect_url);
    exit;
}

// --- Сохранение в БД ---
if ($is_editing) {
    // Обновление пользователя
    $sql_parts = [
        "email = ?",
        "role = ?",
        "full_name = ?",
        "phone_number = ?",
        "address = ?"
    ];
    $params = [$email, $role, $full_name, $phone_number, $address];
    $types = "sssss";

    if ($hashed_password) { // Если задан новый пароль
        $sql_parts[] = "password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    $params[] = $user_id; // Добавляем ID в конец
    $types .= "i";

    $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
         $stmt->bind_param($types, ...$params);
         if ($stmt->execute()) {
              setFlashMessage('success', 'Данные пользователя успешно обновлены.');
         } else {
             error_log("Ошибка обновления пользователя ID {$user_id}: " . $stmt->error);
             setFlashMessage('error', 'Не удалось обновить пользователя: ' . $stmt->error);
         }
         $stmt->close();
    } else {
         error_log("Ошибка подготовки обновления пользователя: " . $conn->error);
         setFlashMessage('error', 'Ошибка сервера при обновлении пользователя.');
    }

} else {
    // Добавление нового пользователя
    $sql = "INSERT INTO users (username, email, password, role, full_name, phone_number, address) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
         // Типы: s, s, s, s, s, s, s
         $stmt->bind_param("sssssss", $username, $email, $hashed_password, $role, $full_name, $phone_number, $address);
         if ($stmt->execute()) {
              setFlashMessage('success', 'Пользователь успешно добавлен.');
         } else {
             error_log("Ошибка добавления пользователя: " . $stmt->error);
             setFlashMessage('error', 'Не удалось добавить пользователя: ' . $stmt->error);
         }
         $stmt->close();
    } else {
         error_log("Ошибка подготовки добавления пользователя: " . $conn->error);
         setFlashMessage('error', 'Ошибка сервера при добавлении пользователя.');
    }
}

unset($_SESSION['csrf_token']);
unset($_SESSION['form_data']);
header('Location: ' . BASE_URL . 'admin/manage_users.php');
exit;

?>