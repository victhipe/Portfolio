<?php
// --- НАЧАЛО ФАЙЛА checkout.php ---
$page_title = "Оформление заказа";
require_once __DIR__ . '/includes/db_connect.php'; // БД, config
require_once __DIR__ . '/includes/functions.php'; // Функции, сессии

// Определяем коэффициенты цен (дублируем из index/cart/place_order)
define('PRICE_FACTOR_SMALL', 0.7);
define('PRICE_FACTOR_MEDIUM', 1.0);
define('PRICE_FACTOR_LARGE', 1.3);


// 1. Проверяем, есть ли что-то в корзине
$cart_items_session = $_SESSION['cart'] ?? []; // Переименуем для ясности
if (empty($cart_items_session)) {
    setFlashMessage('info', 'Ваша корзина пуста. Сначала добавьте что-нибудь в нее.');
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

// 2. Проверяем авторизацию (остается без изменений)
if (!isLoggedIn()) {
    setFlashMessage('info', 'Пожалуйста, войдите или зарегистрируйтесь, чтобы оформить заказ.');
    $_SESSION['redirect_after_login'] = BASE_URL . 'checkout.php';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// 3. Получаем данные пользователя (остается без изменений)
$user_id = getCurrentUserId();
$user_data = null;
$sql_user = "SELECT full_name, phone_number, address FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
if($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_data = $result_user->fetch_assoc();
    $stmt_user->close();
}

// --- 4. Получаем детали корзины для отображения и расчета суммы (ПЕРЕРАБОТАНО) ---
$cart_details_for_display = []; // Массив для вывода в HTML
$total_price = 0;
$item_ids_to_fetch = []; // ID товаров для запроса к БД

// 4.1 Собираем уникальные ID товаров из ключей корзины
foreach ($cart_items_session as $cart_key => $quantity) {
    list($item_id) = explode('_', $cart_key);
    $item_ids_to_fetch[] = (int)$item_id;
}
$item_ids_to_fetch = array_unique($item_ids_to_fetch);

// 4.2 Получаем данные товаров из БД
$items_db_info = [];
$fetch_error = false;
if (!empty($item_ids_to_fetch)) {
    $placeholders = implode(',', array_fill(0, count($item_ids_to_fetch), '?'));
    $types = str_repeat('i', count($item_ids_to_fetch));
    // --- ИСПРАВЛЕННЫЙ ЗАПРОС ---
    $sql_items_db = "SELECT id, category, name, price, is_available FROM menu_items WHERE id IN ($placeholders)";
    $result_items_db = safeQuery($sql_items_db, $item_ids_to_fetch, $types);

    if ($result_items_db) {
        while ($item_db = $result_items_db->fetch_assoc()) {
            $items_db_info[$item_db['id']] = $item_db;
        }
        $result_items_db->free();
    } else {
        // Ошибка выполнения запроса safeQuery
        $fetch_error = true;
    }
} elseif (!empty($cart_items_session)) {
    // Корзина не пуста, но ID извлечь не удалось (странная ситуация)
    $fetch_error = true;
}

// Если не удалось получить данные из БД
if ($fetch_error) {
     setFlashMessage('error', 'Не удалось загрузить детали корзины. Попробуйте обновить страницу или вернуться в корзину.');
     header('Location: ' . BASE_URL . 'cart.php');
     exit;
}


// 4.3 Формируем массив для отображения и считаем итоговую сумму
$actual_cart_session = []; // Сохраняем корзину после проверки доступности
foreach ($cart_items_session as $cart_key => $quantity) {
    list($item_id, $size) = array_pad(explode('_', $cart_key), 2, null);
    $item_id = (int)$item_id;

    // Пропускаем, если данных о товаре нет (хотя не должно быть после предыдущей проверки)
    if (!isset($items_db_info[$item_id])) {
        unset($_SESSION['cart'][$cart_key]); // На всякий случай чистим сессию
        continue;
    }

    $item_db = $items_db_info[$item_id];

    // Проверка доступности
    if (!$item_db['is_available']) {
        unset($_SESSION['cart'][$cart_key]); // Удаляем из сессии
        setFlashMessage('error', 'Товар "' . escape($item_db['name']) . '" стал недоступен и был удален. Пожалуйста, проверьте корзину.');
        // Устанавливаем флаг, что корзина изменилась
        $_SESSION['cart_updated_on_checkout'] = true;
        continue; // Переходим к следующему товару
    }

    // Если товар доступен, добавляем его в "актуальную" корзину
     $actual_cart_session[$cart_key] = $quantity;

    // Расчет цены за штуку с учетом размера
    $price_per_item = (float)$item_db['price'];
    $display_name = $item_db['name'];
    if ($item_db['category'] === 'pizza' && $size) {
        $display_name .= ' (' . $size . ' см)';
        switch ($size) {
            case '35': $price_per_item *= PRICE_FACTOR_SMALL; break;
            case '55': $price_per_item *= PRICE_FACTOR_LARGE; break;
            case '42':
            default:   $price_per_item *= PRICE_FACTOR_MEDIUM; break;
        }
         $price_per_item = round($price_per_item, 2); // Округляем
    }

    $subtotal = $price_per_item * $quantity;
    $total_price += $subtotal;

    // Сохраняем детали для вывода в HTML-сводке
    $cart_details_for_display[] = [
        'name' => $display_name, // Имя с размером
        'quantity' => $quantity,
        'subtotal' => $subtotal
    ];
}

// 5. Если корзина изменилась из-за недоступных товаров, редирект обратно в корзину
if (isset($_SESSION['cart_updated_on_checkout'])) {
     unset($_SESSION['cart_updated_on_checkout']); // Убираем флаг
     // Обновляем сессию корзины актуальными данными
     $_SESSION['cart'] = $actual_cart_session;
     header('Location: ' . BASE_URL . 'cart.php');
     exit;
}

// 6. Если корзина опустела ПОСЛЕ проверки доступности
if (empty($actual_cart_session)) {
    setFlashMessage('info', 'Все товары в вашей корзине стали недоступны.');
    $_SESSION['cart'] = []; // Очищаем корзину в сессии
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

// --- НАЧАЛО ВЫВОДА HTML ---
require_once __DIR__ . '/includes/header.php';
?>

<h1><?php echo escape($page_title); ?></h1>

<?php
// Показать сообщение об ошибке валидации формы, если оно есть
$error_message = getFlashMessage('error'); // Ошибки от place_order.php
if ($error_message) {
    echo '<div class="flash-message error">' . $error_message . '</div>'; // Может содержать HTML (<br>)
}
?>

<div class="checkout-container">
    <div class="checkout-summary">
        <h2>Ваш заказ:</h2>
        <?php if (!empty($cart_details_for_display)): ?>
            <ul>
                <?php foreach ($cart_details_for_display as $item): ?>
                    <li>
                        <?php echo escape($item['name']); ?> x <?php echo $item['quantity']; ?> = <?php echo number_format($item['subtotal'], 2, ',', ' '); ?> руб.
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Итого к оплате: <span class="total-sum"><?php echo number_format($total_price, 2, ',', ' '); ?> руб.</span></strong></p>
            <p><a href="<?php echo BASE_URL; ?>cart.php" class="button secondary x-small">Изменить заказ</a></p>
        <?php else: ?>
            <p>Ошибка при загрузке сводки заказа.</p>
        <?php endif; ?>
    </div>

    <div class="checkout-form-section">
        <h2>Данные для доставки:</h2>
        <form id="checkout-form" action="<?php echo BASE_URL; ?>actions/place_order.php" method="post">
             <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

             <div>
                 <label for="checkout_name">Ваше имя: <span class="required">*</span></label>
                 <input type="text" id="checkout_name" name="customer_name" value="<?php echo escape($user_data['full_name'] ?? ''); ?>" required maxlength="100">
             </div>
             <div>
                 <label for="checkout_phone">Контактный телефон: <span class="required">*</span></label>
                 <input type="tel" id="checkout_phone" name="phone_number" value="<?php echo escape($user_data['phone_number'] ?? ''); ?>" required pattern="^\+?[0-9\s\-\(\)]+$" maxlength="20">
                  <small>Например: +7 (999) 123-45-67</small>
             </div>
             <div>
                 <label for="checkout_address">Адрес доставки: <span class="required">*</span></label>
                 <textarea id="checkout_address" name="delivery_address" rows="4" required><?php echo escape($user_data['address'] ?? ''); ?></textarea>
             </div>
             <div>
                <label for="checkout_notes">Комментарий к заказу (необязательно):</label>
                <textarea id="checkout_notes" name="operator_notes" rows="3"></textarea>
             </div>
             <div>
                 <button type="submit" class="button large">Подтвердить заказ</button>
             </div>
        </form>
    </div>
</div>

<style>
    /* Стили можно вынести */
    .checkout-container {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
    }
    .checkout-summary {
        flex: 1; /* Занимает доступное пространство */
        min-width: 280px; /* Мин. ширина */
        background: #f9f9f9;
        padding: 20px;
        border: 1px solid #eee;
        border-radius: 5px;
        align-self: flex-start; /* Не растягивается по высоте */
    }
     .checkout-summary h2 { margin-top: 0; }
     .checkout-summary ul { list-style: none; padding: 0; margin-bottom: 15px; }
     .checkout-summary li { margin-bottom: 5px; border-bottom: 1px dotted #ddd; padding-bottom: 5px;}
     .checkout-summary li:last-child { border-bottom: none; }
     .checkout-summary .total-sum { font-size: 1.2em; color: #e8491d; }
     .checkout-summary p { margin-top: 15px; }


    .checkout-form-section {
        flex: 2; /* Занимает в 2 раза больше места, чем сводка */
        min-width: 300px;
    }
     .checkout-form-section h2 { margin-top: 0; }

    /* Стили для формы (label, input и т.д. уже должны быть в style.css) */
    .required { color: red; }
    small { display: block; font-size: 0.8em; color: #666; margin-top: -5px; margin-bottom: 10px; }
    .button.large { padding: 12px 25px; font-size: 1.1em; }
    .button.secondary { background-color: #ccc; color: #333; }
    .button.secondary:hover { background-color: #bbb; }
    .button.x-small { padding: 2px 6px; font-size: 0.8em; }

</style>

<?php
// Подключаем JS для валидации формы заказа
$extra_js = ['validation.js'];
require_once __DIR__ . '/includes/footer.php';
// --- КОНЕЦ ФАЙЛА checkout.php ---
?>