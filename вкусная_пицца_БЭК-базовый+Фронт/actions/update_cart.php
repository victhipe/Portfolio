<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверка CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . BASE_URL . 'cart.php');
     exit;
}

if (isset($_POST['update_cart']) && isset($_POST['quantity']) && is_array($_POST['quantity'])) {
    foreach ($_POST['quantity'] as $cart_key => $quantity) {
        // Валидация ключа (простая - не пустой) и количества
        $cart_key = trim($cart_key); // Убираем пробелы, если вдруг попали
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

        if (!empty($cart_key) && $quantity !== false && isset($_SESSION['cart'][$cart_key])) {
            if ($quantity > 0 && $quantity <= 20) { // Ограничение
                $_SESSION['cart'][$cart_key] = $quantity;
            } else if ($quantity <= 0) {
                unset($_SESSION['cart'][$cart_key]);
                // Можно добавить flash-сообщение об удалении товара
            }
        }
    }
     setFlashMessage('success', 'Корзина обновлена.');
}

// unset($_SESSION['csrf_token']);
header('Location: ' . BASE_URL . 'cart.php');
exit;
?>