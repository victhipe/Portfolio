<?php
require_once __DIR__ . '/config.php'; 

// Очистка всех данных сессии
$_SESSION = array();

// Если используется сессионные cookies, удаляем их
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожение сессии
session_destroy();

// Перенаправление на главную страницу
header('Location: ' . BASE_URL);
exit;
?>