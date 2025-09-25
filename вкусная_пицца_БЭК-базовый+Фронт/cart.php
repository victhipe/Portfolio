<?php
$page_title = "Корзина";
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/header.php';

// Определяем коэффициенты цен для размеров пиццы (дублируем для расчета здесь)
define('PRICE_FACTOR_SMALL', 0.7);
define('PRICE_FACTOR_MEDIUM', 1.0);
define('PRICE_FACTOR_LARGE', 1.3);

$cart_items_session = $_SESSION['cart'] ?? [];
$cart_details = [];
$total_price = 0;
$item_ids_to_fetch = []; // Собираем ID товаров для одного запроса к БД

// 1. Разбираем ключи корзины и собираем ID товаров
foreach ($cart_items_session as $cart_key => $quantity) {
    list($item_id) = explode('_', $cart_key); // Получаем ID товара из ключа
    $item_ids_to_fetch[] = (int)$item_id;
}
$item_ids_to_fetch = array_unique($item_ids_to_fetch); // Убираем дубликаты ID

// 2. Получаем данные о товарах из БД одним запросом
$items_db_info = [];
if (!empty($item_ids_to_fetch)) {
    $placeholders = implode(',', array_fill(0, count($item_ids_to_fetch), '?'));
    $types = str_repeat('i', count($item_ids_to_fetch));
    $sql = "SELECT id, category, name, price, image_path, is_available FROM menu_items WHERE id IN ($placeholders)";
    $result = safeQuery($sql, $item_ids_to_fetch, $types);
    if ($result) {
        while ($item_db = $result->fetch_assoc()) {
            $items_db_info[$item_db['id']] = $item_db; // Сохраняем данные по ID
        }
        $result->free();
    }
}

// 3. Формируем массив для отображения корзины
foreach ($cart_items_session as $cart_key => $quantity) {
    list($item_id, $size) = array_pad(explode('_', $cart_key), 2, null); // Получаем ID и размер (может быть null)
    $item_id = (int)$item_id;

    // Проверяем, есть ли данные о товаре из БД
    if (!isset($items_db_info[$item_id])) {
        unset($_SESSION['cart'][$cart_key]); // Удаляем из корзины, если товар не найден в БД
        setFlashMessage('error', "Товар с ID {$item_id} был удален из меню и убран из корзины.");
        continue;
    }

    $item_db = $items_db_info[$item_id];

    // Проверяем доступность
    if (!$item_db['is_available']) {
         unset($_SESSION['cart'][$cart_key]);
         setFlashMessage('info', 'Товар "' . escape($item_db['name']) . '" стал недоступен и был удален из корзины.');
         continue;
    }

    // Рассчитываем цену за штуку с учетом размера
    $price_per_item = (float)$item_db['price'];
    $display_name = $item_db['name'];
    if ($item_db['category'] === 'pizza' && $size) {
        $display_name .= ' (' . $size . ' см)'; // Добавляем размер к названию
        switch ($size) {
            case '35': $price_per_item *= PRICE_FACTOR_SMALL; break;
            case '55': $price_per_item *= PRICE_FACTOR_LARGE; break;
            case '42': // Fallthrough - default factor is 1.0
            default:   $price_per_item *= PRICE_FACTOR_MEDIUM; break;
        }
    }

    $subtotal = $price_per_item * $quantity;
    $total_price += $subtotal;

    $cart_details[$cart_key] = [ // Используем cart_key как ключ массива
        'id' => $item_id, // Оригинальный ID товара
        'cart_key' => $cart_key, // Уникальный ключ корзины
        'name' => $display_name, // Имя с размером, если нужно
        'category' => $item_db['category'],
        'size' => $size, // Размер (для пиццы)
        'image_path' => $item_db['image_path'],
        'quantity' => $quantity,
        'price_per_item' => $price_per_item, // Цена с учетом размера
        'subtotal' => $subtotal
    ];
}

?>

<h1><?php echo escape($page_title); ?></h1>

<?php if (!empty($cart_details)): ?>
    <form action="<?php echo BASE_URL; ?>actions/update_cart.php" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <table class="cart-table">
            <thead>
                <tr>
                    <th colspan="2">Товар</th>
                    <th>Цена за шт.</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                    <th>Удалить</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_details as $cart_key => $item): ?>
                     <?php
                        $image_url = BASE_URL . 'images/' . (!empty($item['image_path']) && file_exists(__DIR__ . '/images/' . $item['image_path']) ? escape($item['image_path']) : 'placeholder.png');
                     ?>
                    <tr>
                        <td><img src="<?php echo $image_url; ?>" alt="<?php echo escape($item['name']); ?>"></td>
                        <td><?php echo escape($item['name']); ?></td>
                        <td><?php echo number_format($item['price_per_item'], 2, ',', ' '); ?> руб.</td>
                        <td>
                            <!-- В name используем cart_key -->
                            <input type="number" name="quantity[<?php echo $cart_key; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="20" required>
                        </td>
                        <td><?php echo number_format($item['subtotal'], 2, ',', ' '); ?> руб.</td>
                        <td>
                             <!-- В ссылке используем cart_key -->
                             <a href="<?php echo BASE_URL; ?>actions/remove_from_cart.php?key=<?php echo urlencode($cart_key); ?>&token=<?php echo generateCsrfToken(); ?>" title="Удалить">❌</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-actions">
             <button type="submit" name="update_cart" class="button">Обновить корзину</button>
             <span></span>
        </div>
    </form>

    <div class="cart-total">
        Общая сумма: <?php echo number_format($total_price, 2, ',', ' '); ?> руб.
    </div>

    <div style="text-align: right;">
        <a href="<?php echo BASE_URL; ?>checkout.php" class="button">Оформить заказ</a>
    </div>

<?php else: ?>
    <p>Ваша корзина пуста. Пора выбрать что-нибудь <a href="<?php echo BASE_URL; ?>">вкусненькое</a>!</p>
<?php endif; ?>


<?php
require_once __DIR__ . '/includes/footer.php';
?>