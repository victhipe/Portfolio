<?php
require_once __DIR__ . '/../config.php'; // Для доступа к BASE_URL и сессии

// Проверка, авторизован ли пользователь
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Получение ID текущего пользователя
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Получение роли текущего пользователя
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Проверка роли пользователя
function checkUserRole($required_role) {
    if (!isLoggedIn() || getCurrentUserRole() !== $required_role) {
        // Можно добавить сообщение об ошибке в сессию
        $_SESSION['error_message'] = "Доступ запрещен.";
        header('Location: ' . BASE_URL . 'login.php'); // Перенаправить на вход
        exit;
    }
}

// Проверка доступа для администратора или оператора
function checkAdminOrOperator() {
     if (!isLoggedIn() || !in_array(getCurrentUserRole(), ['admin', 'operator'])) {
        $_SESSION['error_message'] = "Доступ запрещен.";
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}


// Функция для безопасного вывода данных в HTML
function escape($html) {
    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}

// Генерация CSRF токена (базовая реализация)
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка CSRF токена
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Добавление сообщения для пользователя (flash message)
function setFlashMessage($key, $message) {
    $_SESSION['flash_messages'][$key] = $message;
}

// Получение и удаление flash сообщения
function getFlashMessage($key) {
    if (isset($_SESSION['flash_messages'][$key])) {
        $message = $_SESSION['flash_messages'][$key];
        unset($_SESSION['flash_messages'][$key]);
        return $message;
    }
    return null;
}

// $basePrice - цена из БД (для 42см)
// $size - строка '35cm', '42cm', '55cm'
function calculatePizzaPrice($basePrice, $size) {
    $price = (float)$basePrice;
    switch ($size) {
        case '35cm':
            // Уменьшаем на 30%
            return round($price * 0.7, 2);
        case '55cm':
            // Увеличиваем на 30%
            return round($price * 1.3, 2);
        case '42cm':
        default: // По умолчанию или если размер некорректен - базовая цена
            return round($price, 2);
    }
}

// Функция для получения категорий
function getCategories() {
    global $conn; // Используем глобальное соединение
    $categories = [];
    $sql = "SELECT id, name, slug FROM categories ORDER BY sort_order ASC, name ASC";
    $result = $conn->query($sql); // Прямой запрос, т.к. нет параметров
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        $result->free();
    }
    return $categories;
}

?>