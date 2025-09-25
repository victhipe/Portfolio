<?php
// --- НАЧАЛО ФАЙЛА actions/place_order.php ---
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Коэффициенты цен
define('PRICE_FACTOR_SMALL', 0.7);
define('PRICE_FACTOR_MEDIUM', 1.0);
define('PRICE_FACTOR_LARGE', 1.3);

// --- Проверки CSRF ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . BASE_URL . 'checkout.php');
     exit;
}

// --- Проверка Авторизации и ПОЛУЧЕНИЕ User ID ---
if (!isLoggedIn()) {
    setFlashMessage('info', 'Ваша сессия истекла. Пожалуйста, войдите снова для оформления заказа.');
     $_SESSION['redirect_after_login'] = BASE_URL . 'checkout.php';
     unset($_SESSION['form_data']);
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
$user_id = getCurrentUserId();

// --- Первичная Проверка User ID из сессии ---
if ($user_id === null || !filter_var($user_id, FILTER_VALIDATE_INT) || $user_id <= 0) {
    session_regenerate_id(true);
    setFlashMessage('error', 'Ошибка сессии пользователя (ID invalid). Пожалуйста, войдите снова.');
     unset($_SESSION['form_data']);
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// --- Проверка Корзины ---
$cart_items_session = $_SESSION['cart'] ?? [];
if (empty($cart_items_session)) {
     setFlashMessage('info', 'Ваша корзина пуста.');
     header('Location: ' . BASE_URL . 'cart.php');
     exit;
}

// --- Получение и валидация данных формы ---
$customer_name = trim($_POST['customer_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$delivery_address = trim($_POST['delivery_address'] ?? '');
$operator_notes = trim($_POST['operator_notes'] ?? '');

$errors = [];
if (empty($customer_name)) { $errors[] = "Имя обязательно для заполнения."; }
if (empty($phone_number)) { $errors[] = "Контактный телефон обязателен для заполнения."; }
elseif (!preg_match('/^\+?[0-9\s\-\(\)]{5,}$/', $phone_number)) { $errors[] = "Неверный формат или слишком короткий номер телефона."; }
if (empty($delivery_address)) { $errors[] = "Адрес доставки обязателен для заполнения."; }

// --- Проверка ошибок валидации ---
if (!empty($errors)) {
    $_SESSION['form_data'] = $_POST;
    unset($_SESSION['form_data']['csrf_token']);
    setFlashMessage('error', implode('<br>', $errors));
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

// --- НАЧАЛО ТРАНЗАКЦИИ ---
$conn->begin_transaction();

try {
    // 1. Получаем ID товаров из корзины
    // ... (код как был) ...
    $item_ids_to_fetch = [];
    foreach ($cart_items_session as $cart_key => $quantity) { list($item_id) = explode('_', $cart_key); $item_ids_to_fetch[] = (int)$item_id; }
    $item_ids_to_fetch = array_unique($item_ids_to_fetch);
    if (empty($item_ids_to_fetch)) throw new Exception("Корзина оказалась пуста после обработки.");

    // 2. Получаем актуальные данные товаров из БД
    // ... (код как был) ...
    $items_db_info = [];
    $placeholders = implode(',', array_fill(0, count($item_ids_to_fetch), '?'));
    $types = str_repeat('i', count($item_ids_to_fetch));
    $sql_items_db = "SELECT id, category, name, price, is_available FROM menu_items WHERE id IN ($placeholders)";
    $stmt_items_db = $conn->prepare($sql_items_db);
    if (!$stmt_items_db) throw new Exception("Ошибка подготовки запроса товаров: " . $conn->error);
    $stmt_items_db->bind_param($types, ...$item_ids_to_fetch);
    if (!$stmt_items_db->execute()) throw new Exception("Ошибка выполнения запроса товаров: " . $stmt_items_db->error);
    $result_items_db = $stmt_items_db->get_result();
    while ($item_db = $result_items_db->fetch_assoc()) { $items_db_info[$item_db['id']] = $item_db; }
    $stmt_items_db->close();
    if (count($items_db_info) !== count($item_ids_to_fetch)) { throw new Exception("Некоторые товары из корзины не найдены в меню."); }

    // 3. Пересчитываем сумму и готовим данные для order_items
    // ... (код как был, включая инициализацию $total_price = 0.0;) ...
    $total_price = 0.0;
    $order_items_data = [];
    $actual_cart_session = [];
    foreach ($cart_items_session as $cart_key => $quantity) {
        // ... (расчет price_per_item, проверка доступности) ...
         list($item_id, $size) = array_pad(explode('_', $cart_key), 2, null);
         $item_id = (int)$item_id; $item_db = $items_db_info[$item_id];
         if (!$item_db['is_available']) { unset($_SESSION['cart'][$cart_key]); throw new Exception('Товар "' . escape($item_db['name']) . '" стал недоступен.'); }
         $actual_cart_session[$cart_key] = $quantity;
         $price_per_item = (float)$item_db['price'];
         if ($item_db['category'] === 'pizza') { if(!$size) throw new Exception("Не указан размер для пиццы '".escape($item_db['name'])."'."); switch ($size) { case '35': $price_per_item *= PRICE_FACTOR_SMALL; break; case '55': $price_per_item *= PRICE_FACTOR_LARGE; break; default: $price_per_item *= PRICE_FACTOR_MEDIUM; break; } $price_per_item = round($price_per_item, 2); }
         if (!is_numeric($price_per_item) || !is_numeric($quantity)) { error_log("Ошибка расчета цены для item_id {$item_id}"); throw new Exception("Внутренняя ошибка расчета цены."); }
         $total_price += $price_per_item * $quantity;
         $order_items_data[] = [ 'menu_item_id' => $item_id, 'size' => ($item_db['category'] === 'pizza') ? $size : null, 'quantity' => $quantity, 'price_per_item' => $price_per_item ];
    }
     // --- Проверка итоговой суммы и позиций заказа ---
    if (!is_numeric($total_price)) { error_log("Критическая ошибка: total_price не число: " . var_export($total_price, true)); throw new Exception("Ошибка расчета итоговой суммы."); }
    if ($total_price < 0) { error_log("Критическая ошибка: total_price < 0: " . $total_price); throw new Exception("Итоговая сумма не может быть отрицательной."); }
    if (empty($order_items_data)) { throw new Exception("Не удалось сформировать позиции заказа."); }
    if (count($actual_cart_session) !== count($cart_items_session)) { $_SESSION['cart'] = $actual_cart_session; throw new Exception("Корзина изменилась. Проверьте и попробуйте снова."); }


    // --- >>> ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА USER ID В БАЗЕ ДАННЫХ <<< ---
    $sql_check_user = "SELECT id FROM users WHERE id = ?";
    $stmt_check_user = $conn->prepare($sql_check_user);
    if (!$stmt_check_user) {
        error_log("Ошибка подготовки проверки пользователя: " . $conn->error);
        throw new Exception("Ошибка сервера при проверке данных пользователя.");
    }
    $stmt_check_user->bind_param("i", $user_id);
    if (!$stmt_check_user->execute()) {
        error_log("Ошибка выполнения проверки пользователя: " . $stmt_check_user->error);
        throw new Exception("Ошибка сервера при проверке данных пользователя.");
    }
    $result_check_user = $stmt_check_user->get_result();
    if ($result_check_user->num_rows !== 1) {
        // Пользователя с таким ID НЕТ в БД!
        $stmt_check_user->close();
        error_log("Ошибка FK Constraint: User ID {$user_id} из сессии не найден в таблице users.");
        // Сбрасываем сессию и отправляем на логин
        session_destroy(); // Уничтожаем проблемную сессию
        setFlashMessage('error', 'Ошибка данных пользователя. Пожалуйста, войдите в систему заново.');
        header('Location: ' . BASE_URL . 'login.php');
        exit; // Важно выйти здесь, чтобы не продолжать транзакцию
    }
    $stmt_check_user->close(); // Закрываем стейтмент, если пользователь найден
    // --- >>> КОНЕЦ ДОПОЛНИТЕЛЬНОЙ ПРОВЕРКИ <<< ---


    // 4. Вставляем запись в таблицу `orders`
    $sql_order = "INSERT INTO orders (user_id, customer_name, phone_number, delivery_address, total_price, status, operator_notes)
                  VALUES (?, ?, ?, ?, ?, 'new', ?)";
    $stmt_order = $conn->prepare($sql_order);
    if (!$stmt_order) throw new Exception("Ошибка подготовки запроса заказа: " . $conn->error);

    // Привязка параметров
    $stmt_order->bind_param("isssds",
        $user_id, // Теперь мы ТОЧНО знаем, что этот ID существует в users
        $customer_name,
        $phone_number,
        $delivery_address,
        $total_price,
        $operator_notes
    );

    if (!$stmt_order->execute()) {
         // Логируем и проверяем ошибку
         error_log("MySQL Error on Orders Insert (errno: {$conn->errno}): {$conn->error}");
         if ($conn->errno == 1048) { // Column cannot be null (на случай других NULL проблем)
              throw new Exception("Ошибка данных заказа: одно из обязательных полей не заполнено.");
         } else {
              throw new Exception("Ошибка сохранения данных заказа."); // Общая ошибка
         }
    }
    $order_id = $stmt_order->insert_id;
    $stmt_order->close();

    // 5. Вставляем записи в таблицу `order_items`
    // ... (код как был) ...
    $sql_item_insert = "INSERT INTO order_items (order_id, menu_item_id, size, quantity, price_per_item) VALUES (?, ?, ?, ?, ?)";
    $stmt_item_insert = $conn->prepare($sql_item_insert);
    if (!$stmt_item_insert) throw new Exception("Ошибка подготовки запроса позиций заказа: " . $conn->error);
    foreach ($order_items_data as $item_data) {
        $stmt_item_insert->bind_param("iisid", $order_id, $item_data['menu_item_id'], $item_data['size'], $item_data['quantity'], $item_data['price_per_item']);
        if (!$stmt_item_insert->execute()) { error_log("MySQL Error on Order Items Insert (errno: {$conn->errno}): {$conn->error}"); throw new Exception("Ошибка сохранения позиции заказа (ID: {$item_data['menu_item_id']})"); }
    }
    $stmt_item_insert->close();


    // 6. Все успешно - подтверждаем транзакцию
    $conn->commit();

    // 7. Очищаем корзину, токен, сессию формы, сохраняем ID заказа
    unset($_SESSION['cart']);
    unset($_SESSION['csrf_token']);
    unset($_SESSION['form_data']);
    $_SESSION['last_order_id'] = $order_id;

    // 8. Редирект на страницу успеха
    header('Location: ' . BASE_URL . 'order_success.php');
    exit;

} catch (Exception $e) {
    // Откат транзакции при ошибке
    $conn->rollback();

    error_log("Ошибка оформления заказа (place_order.php): " . $e->getMessage() . " | User ID: " . var_export($user_id, true));
    setFlashMessage('error', "Не удалось оформить заказ: " . $e->getMessage());

    // Сохраняем данные формы для повторного заполнения
    $_SESSION['form_data'] = $_POST;
    unset($_SESSION['form_data']['csrf_token']);

    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}
// --- КОНЕЦ ФАЙЛА actions/place_order.php ---
?>