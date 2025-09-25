<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db_connect.php'; // Нужен для проверки категории товара

// Проверка CSRF токена
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
     exit;
}

if (isset($_POST['menu_item_id']) && filter_var($_POST['menu_item_id'], FILTER_VALIDATE_INT)) {
    $item_id = (int)$_POST['menu_item_id'];
    $quantity = isset($_POST['quantity']) && filter_var($_POST['quantity'], FILTER_VALIDATE_INT) && $_POST['quantity'] > 0 ? (int)$_POST['quantity'] : 1;
    $size = isset($_POST['size']) ? trim($_POST['size']) : null; // Получаем размер, если он есть

    // --- Проверяем товар в БД (категория и доступность) ---
    $sql_item = "SELECT category, name, is_available FROM menu_items WHERE id = ?";
    $item_info = null;
    $stmt_item = $conn->prepare($sql_item);
    if ($stmt_item) {
         $stmt_item->bind_param("i", $item_id);
         $stmt_item->execute();
         $result_item = $stmt_item->get_result();
         $item_info = $result_item->fetch_assoc();
         $stmt_item->close();
    }

    if (!$item_info) {
        setFlashMessage('error', 'Товар не найден.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
        exit;
    }

    if (!$item_info['is_available']) {
        setFlashMessage('error', 'Товар "' . escape($item_info['name']) . '" временно недоступен.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
        exit;
    }

    // --- Определяем ключ для корзины ---
    $cart_key = $item_id; // По умолчанию ключ - это ID товара
    if ($item_info['category'] === 'pizza') {
        // Для пиццы ключ должен включать размер
        $allowed_sizes = ['35', '42', '55'];
        if ($size && in_array($size, $allowed_sizes)) {
            $cart_key = $item_id . '_' . $size; // Формат: ID_РАЗМЕР, например "1_35"
        } else {
             setFlashMessage('error', 'Не выбран или неверный размер для пиццы.');
             header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
             exit;
        }
    }

    // Инициализация корзины в сессии
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Добавление или обновление количества
    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key] += $quantity;
    } else {
        $_SESSION['cart'][$cart_key] = $quantity;
    }

     setFlashMessage('success', escape($item_info['name']) . ($size ? ' ('.$size.' см)' : '') . ' добавлено в корзину!');
     // unset($_SESSION['csrf_token']); // Опционально

} else {
    setFlashMessage('error', 'Неверный ID товара.');
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . 'cart.php'));
exit;
?>