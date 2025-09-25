<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Требуем авторизацию
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user_id = getCurrentUserId();

// Проверка CSRF токена
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . BASE_URL . 'profile_edit.php');
     exit;
}

// Получение данных из формы
$email = trim($_POST['email'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$address = trim($_POST['address'] ?? '');
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Получаем текущие данные пользователя из БД для сравнения и проверки пароля
$current_user_data = null;
$sql_current = "SELECT email, password FROM users WHERE id = ?";
$stmt_current = $conn->prepare($sql_current);
if(!$stmt_current) {
    error_log("Ошибка подготовки запроса текущих данных пользователя: " . $conn->error);
    setFlashMessage('error', 'Ошибка сервера при проверке данных.');
    header('Location: ' . BASE_URL . 'profile_edit.php');
    exit;
}
$stmt_current->bind_param("i", $user_id);
$stmt_current->execute();
$result_current = $stmt_current->get_result();
$current_user_data = $result_current->fetch_assoc();
$stmt_current->close();

if (!$current_user_data) {
     setFlashMessage('error', 'Не удалось получить текущие данные пользователя.');
     header('Location: ' . BASE_URL . 'profile.php'); // На основной профиль
     exit;
}

$errors = [];
$update_fields = []; // Поля для обновления в БД
$update_params = []; // Параметры для bind_param
$update_types = "";   // Типы для bind_param

// --- Валидация и подготовка данных ---

// Email
if (empty($email)) {
    $errors[] = "Email обязателен.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Неверный формат Email.";
} elseif ($email !== $current_user_data['email']) {
    // Email изменился - проверяем уникальность и требуем текущий пароль
    if (empty($current_password)) {
        $errors[] = "Введите текущий пароль для подтверждения смены Email.";
    } elseif (!password_verify($current_password, $current_user_data['password'])) {
        $errors[] = "Текущий пароль введен неверно.";
    } else {
        // Проверка уникальности нового email
        $sql_check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("si", $email, $user_id);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();
        if ($result_check_email->num_rows > 0) {
            $errors[] = "Этот Email уже используется другим пользователем.";
        }
        $stmt_check_email->close();

        if (empty($errors)) { // Добавляем email в обновление только если все проверки пройдены
            $update_fields[] = "email = ?";
            $update_params[] = $email;
            $update_types .= "s";
        }
    }
}

// Телефон (простая валидация)
if (!empty($phone_number) && !preg_match('/^\+?[0-9\s\-\(\)]+$/', $phone_number)) {
    $errors[] = "Неверный формат номера телефона.";
} else {
     $update_fields[] = "phone_number = ?";
     $update_params[] = $phone_number;
     $update_types .= "s";
}

// Полное имя и адрес - просто добавляем в обновление
$update_fields[] = "full_name = ?";
$update_params[] = $full_name;
$update_types .= "s";

$update_fields[] = "address = ?";
$update_params[] = $address;
$update_types .= "s";


// Пароль
$update_password = false;
if (!empty($new_password)) {
    if (empty($current_password)) {
        $errors[] = "Введите текущий пароль для установки нового.";
    } elseif (!password_verify($current_password, $current_user_data['password'])) {
        $errors[] = "Текущий пароль введен неверно.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Новый пароль должен быть не менее 6 символов.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "Новые пароли не совпадают.";
    } else {
        // Все проверки пройдены, готовим пароль к обновлению
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_fields[] = "password = ?";
        $update_params[] = $hashed_password;
        $update_types .= "s";
        $update_password = true;
    }
} elseif (!empty($current_password) && empty($new_password) && $email === $current_user_data['email']) {
    // Если введен текущий пароль, но не введен новый и email не менялся - это может сбить с толку
    // Можно добавить предупреждение или просто игнорировать
    // $errors[] = "Вы ввели текущий пароль, но не указали новый. Пароль не будет изменен.";
}

// --- Обработка ошибок валидации ---
if (!empty($errors)) {
    $_SESSION['form_data'] = $_POST; // Сохраняем введенные данные
    unset($_SESSION['form_data']['current_password'], $_SESSION['form_data']['new_password'], $_SESSION['form_data']['confirm_password']); // Не сохраняем пароли
    setFlashMessage('error', implode('<br>', $errors));
    header('Location: ' . BASE_URL . 'profile_edit.php');
    exit;
}

// --- Обновление данных в БД ---
if (!empty($update_fields)) {
    $sql_update = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $update_types .= "i"; // Добавляем тип для ID пользователя
    $update_params[] = $user_id; // Добавляем ID пользователя в конец параметров

    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update === false) {
        error_log("Ошибка подготовки обновления профиля: " . $conn->error);
        setFlashMessage('error', 'Ошибка сервера при обновлении профиля.');
    } else {
        $stmt_update->bind_param($update_types, ...$update_params);
        if ($stmt_update->execute()) {
            setFlashMessage('success', 'Профиль успешно обновлен.' . ($update_password ? ' Пароль изменен.' : ''));
            unset($_SESSION['csrf_token']); // Сброс токена
            unset($_SESSION['form_data']);
             header('Location: ' . BASE_URL . 'profile.php'); // Перенаправление на страницу профиля
             exit;
        } else {
            error_log("Ошибка выполнения обновления профиля: " . $stmt_update->error);
            setFlashMessage('error', 'Не удалось обновить профиль: ' . $stmt_update->error);
        }
        $stmt_update->close();
    }
} else {
     // Если не было полей для обновления (например, пользователь ничего не изменил)
     setFlashMessage('info', 'Изменений для сохранения не найдено.');
}

// Если дошли сюда (не было редиректа после успеха или были ошибки БД) - возвращаем на форму редактирования
$_SESSION['form_data'] = $_POST;
unset($_SESSION['form_data']['current_password'], $_SESSION['form_data']['new_password'], $_SESSION['form_data']['confirm_password']);
header('Location: ' . BASE_URL . 'profile_edit.php');
exit;

?>