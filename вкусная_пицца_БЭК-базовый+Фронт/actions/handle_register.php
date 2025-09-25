<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверка CSRF токена
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . BASE_URL . 'register.php');
     exit;
}

// Получение и простая валидация данных
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$full_name = trim($_POST['full_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$address = trim($_POST['address'] ?? '');

$errors = [];

// --- Валидация ---
if (empty($username)) { $errors[] = "Имя пользователя обязательно."; }
elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) { $errors[] = "Имя пользователя содержит недопустимые символы или неверную длину."; }

if (empty($email)) { $errors[] = "Email обязателен."; }
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Неверный формат Email."; }

if (empty($password)) { $errors[] = "Пароль обязателен."; }
elseif (strlen($password) < 6) { $errors[] = "Пароль должен быть не менее 6 символов."; }
elseif ($password !== $password_confirm) { $errors[] = "Пароли не совпадают."; }

if (!empty($phone_number) && !preg_match('/^\+?[0-9\s\-\(\)]+$/', $phone_number)) {
    $errors[] = "Неверный формат номера телефона.";
}
// Длина остальных полей проверяется атрибутами maxlength в HTML

// --- Проверка уникальности ---
if (empty($errors)) {
    // Проверка уникальности username
    $sql_check_user = "SELECT id FROM users WHERE username = ?";
    $stmt_check_user = $conn->prepare($sql_check_user);
    $stmt_check_user->bind_param("s", $username);
    $stmt_check_user->execute();
    $result_check_user = $stmt_check_user->get_result();
    if ($result_check_user->num_rows > 0) {
        $errors[] = "Это имя пользователя уже занято.";
    }
    $stmt_check_user->close();

    // Проверка уникальности email
    $sql_check_email = "SELECT id FROM users WHERE email = ?";
    $stmt_check_email = $conn->prepare($sql_check_email);
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $result_check_email = $stmt_check_email->get_result();
    if ($result_check_email->num_rows > 0) {
        $errors[] = "Этот email уже зарегистрирован.";
    }
    $stmt_check_email->close();
}


// --- Если есть ошибки, возвращаем на форму ---
if (!empty($errors)) {
    // Сохраняем введенные данные (кроме пароля) для удобства пользователя
    $_SESSION['form_data'] = $_POST; // В реальном приложении нужно быть осторожнее с этим
    unset($_SESSION['form_data']['password'], $_SESSION['form_data']['password_confirm']);

    setFlashMessage('error', implode('<br>', $errors));
    header('Location: ' . BASE_URL . 'register.php');
    exit;
}

// --- Ошибок нет, регистрируем пользователя ---

// Хешируем пароль
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Добавляем пользователя в БД
$sql_insert = "INSERT INTO users (username, email, password, full_name, phone_number, address, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')";
$stmt_insert = $conn->prepare($sql_insert);
if ($stmt_insert === false) {
     error_log("Ошибка подготовки запроса на вставку пользователя: " . $conn->error);
     setFlashMessage('error', 'Произошла ошибка сервера при регистрации.');
     header('Location: ' . BASE_URL . 'register.php');
     exit;
}

// Привязываем параметры
// Типы: s - string, s, s, s, s, s
$stmt_insert->bind_param("ssssss",
    $username,
    $email,
    $hashed_password,
    $full_name,
    $phone_number,
    $address
);

if ($stmt_insert->execute()) {
    // Успешная регистрация
    $user_id = $stmt_insert->insert_id; // Получаем ID нового пользователя
    $stmt_insert->close();

    // Удаляем сохраненные данные формы
    unset($_SESSION['form_data']);
    unset($_SESSION['csrf_token']); // Сбрасываем токен

    // Можно сразу авторизовать пользователя
    // session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['user_role'] = 'customer';

    setFlashMessage('success', 'Регистрация прошла успешно! Теперь вы можете оформить заказ.');
    // Перенаправляем в профиль или на главную
     if (!empty($_SESSION['cart'])) {
         header('Location: ' . BASE_URL . 'checkout.php'); // Если есть корзина, сразу на оформление
     } else {
         header('Location: ' . BASE_URL . 'profile.php');
     }
    exit;

} else {
    // Ошибка выполнения запроса
    error_log("Ошибка выполнения запроса на вставку пользователя: " . $stmt_insert->error);
    setFlashMessage('error', 'Произошла ошибка при сохранении данных. Попробуйте еще раз.');
    $stmt_insert->close();
     // Сохраняем данные для формы
    $_SESSION['form_data'] = $_POST;
    unset($_SESSION['form_data']['password'], $_SESSION['form_data']['password_confirm']);
    header('Location: ' . BASE_URL . 'register.php');
    exit;
}
?>