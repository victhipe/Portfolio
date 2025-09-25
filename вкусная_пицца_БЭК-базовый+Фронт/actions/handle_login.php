<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверка CSRF токена
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . BASE_URL . 'login.php');
     exit;
}


if (isset($_POST['username_or_email'], $_POST['password'])) {
    $login = trim($_POST['username_or_email']);
    $password = $_POST['password'];

    if (empty($login) || empty($password)) {
        setFlashMessage('error', 'Пожалуйста, заполните все поля.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    // Ищем пользователя по имени пользователя или email
    $sql = "SELECT id, username, password, role FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Ошибка подготовки запроса: " . $conn->error);
        setFlashMessage('error', 'Произошла ошибка сервера. Попробуйте позже.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        // Пароль верный, начинаем сессию
        // session_regenerate_id(true); // Защита от фиксации сессии

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];

        // Удаляем токен после успешного входа
        unset($_SESSION['csrf_token']);

        // Перенаправление в зависимости от роли
        if ($user['role'] === 'admin') {
            header('Location: ' . BASE_URL . 'admin/');
        } elseif ($user['role'] === 'operator') {
             header('Location: ' . BASE_URL . 'operator/');
        } else {
             // Проверяем, есть ли товары в корзине перед оформлением
             if (!empty($_SESSION['cart'])) {
                  header('Location: ' . BASE_URL . 'checkout.php'); // Направляем сразу на оформление
             } else {
                  header('Location: ' . BASE_URL . 'profile.php'); // Или в профиль
             }
        }
        exit;

    } else {
        // Неверные учетные данные
        setFlashMessage('error', 'Неверное имя пользователя/email или пароль.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

} else {
    // Не все данные были отправлены
    setFlashMessage('error', 'Пожалуйста, заполните все поля.');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
?>