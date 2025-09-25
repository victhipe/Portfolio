<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверка CSRF (GET - менее безопасно)
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['token']) || !verifyCsrfToken($_GET['token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . BASE_URL . 'cart.php');
     exit;
}

if (isset($_GET['key']) && !empty(trim($_GET['key']))) {
    $cart_key = trim($_GET['key']);

    if (isset($_SESSION['cart'][$cart_key])) {
        unset($_SESSION['cart'][$cart_key]);
        setFlashMessage('success', 'Товар удален из корзины.');
    } else {
         setFlashMessage('info', 'Этого товара уже не было в корзине.');
    }
} else {
     setFlashMessage('error', 'Неверный ключ товара для удаления.');
}

// unset($_SESSION['csrf_token']);
header('Location: ' . BASE_URL . 'cart.php');
exit;
?>