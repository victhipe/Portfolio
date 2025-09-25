<?php
// --- НАЧАЛО ФАЙЛА view_order.php ---
$page_title = "Детали заказа";
require_once __DIR__ . '/includes/db_connect.php'; // Подключение к БД и config
require_once __DIR__ . '/includes/functions.php'; // Функции и старт сессии

// 1. Требуем авторизацию
if (!isLoggedIn()) {
    setFlashMessage('info', 'Пожалуйста, войдите, чтобы просмотреть детали заказа.');
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI']; // Запомним куда вернуться
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user_id = getCurrentUserId();
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// 2. Проверяем ID заказа
if (!$order_id) {
    setFlashMessage('error', 'Не указан ID заказа.');
    header('Location: ' . BASE_URL . 'profile.php');
    exit;
}

// --- 3. Получение данных заказа ---
$order_details = null;
$order_items = [];

// Получаем основную информацию о заказе, ПРОВЕРЯЯ, что он принадлежит текущему пользователю
$sql_detail = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt_detail = $conn->prepare($sql_detail);
if($stmt_detail){
    $stmt_detail->bind_param("ii", $order_id, $user_id);
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    $order_details = $result_detail->fetch_assoc();
    $stmt_detail->close();
} else {
     // Ошибка подготовки запроса - логируем и сообщаем пользователю
     error_log("Ошибка подготовки запроса деталей заказа для пользователя ID {$user_id}, Заказ ID {$order_id}: " . $conn->error);
     setFlashMessage('error', 'Ошибка сервера при получении деталей заказа. Попробуйте позже.');
     header('Location: ' . BASE_URL . 'profile.php');
     exit;
}


// 4. Если заказ не найден или не принадлежит пользователю
if (!$order_details) {
     setFlashMessage('error', 'Заказ не найден или у вас нет прав на его просмотр.');
     header('Location: ' . BASE_URL . 'profile.php');
     exit;
}

// 5. Получаем позиции заказа (если основная информация найдена)
$sql_items = "SELECT oi.quantity, oi.price_per_item, oi.size, p.name as item_name, p.image_path
              FROM order_items oi
              JOIN menu_items p ON oi.menu_item_id = p.id
              WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$items_fetch_error = false; // Флаг для отслеживания ошибки получения позиций
 if($stmt_items){
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    while ($item = $result_items->fetch_assoc()) {
        $order_items[] = $item; // Заполняем массив позиций
    }
    $stmt_items->close();
} else {
     // Ошибка подготовки запроса позиций - логируем
     error_log("Ошибка подготовки запроса позиций заказа ID {$order_id}: " . $conn->error);
     $items_fetch_error = true; // Устанавливаем флаг ошибки
}

// --- НАЧАЛО ВЫВОДА HTML ---
require_once __DIR__ . '/includes/header.php';
?>

<h1><?php echo escape($page_title); ?> #<?php echo $order_details['id']; ?></h1>

<div class="order-details-view">
    <p><a href="<?php echo BASE_URL; ?>profile.php" class="button secondary small">← Вернуться к списку заказов</a></p>

    <h2>Информация о заказе</h2>
    <div class="order-info">
        <div><strong>Статус:</strong> <span class="order-status-<?php echo escape($order_details['status']); ?>"><?php echo escape(ucfirst($order_details['status'])); ?></span></div>
        <div><strong>Дата и время:</strong> <?php echo date('d.m.Y H:i', strtotime($order_details['order_time'])); ?></div>
        <div><strong>Имя получателя:</strong> <?php echo escape($order_details['customer_name']); ?></div>
        <div><strong>Телефон:</strong> <?php echo escape($order_details['phone_number']); ?></div>
        <div><strong>Адрес доставки:</strong> <?php echo nl2br(escape($order_details['delivery_address'])); ?></div>
        <div><strong>Комментарий к заказу:</strong> <?php echo nl2br(escape($order_details['operator_notes'] ?: 'Нет')); ?></div>
        <div><strong>Итоговая сумма:</strong> <strong class="total-sum"><?php echo number_format($order_details['total_price'], 2, ',', ' '); ?> руб.</strong></div>
    </div>

    <hr>

    <h2>Состав заказа</h2>
    <?php if ($items_fetch_error): // Если была ошибка получения позиций ?>
        <p class="flash-message error">Не удалось загрузить состав заказа. Пожалуйста, попробуйте обновить страницу или обратитесь в поддержку.</p>
    <?php elseif (!empty($order_items)): // Если ошибки не было и позиции есть ?>
        <ul class="order-items-list">
            <?php foreach ($order_items as $item): ?>
                <li>
                     <?php
                        // Путь к картинке (относительно /images/)
                        $image_relative_path = !empty($item['image_path']) ? $item['image_path'] : 'placeholder.png';
                        $image_full_path = __DIR__ . '/images/' . $image_relative_path;
                        $image_url = BASE_URL . 'images/' . escape($image_relative_path);
                        // Проверяем существование файла, чтобы не показывать битую ссылку
                        if (!file_exists($image_full_path)) {
                             $image_url = BASE_URL . 'images/placeholder.png';
                        }
                     ?>
                     <img src="<?php echo $image_url; ?>" alt="<?php echo escape($item['item_name']); ?>">
                     <div class="item-details">
                         <?php // Вывод названия с размером для пиццы ?>
                         <span class="item-name">
                             <?php echo escape($item['item_name']); ?>
                             <?php if(!empty($item['size'])) echo ' (' . escape($item['size']) . ' см)'; ?>
                         </span>
                         <span class="item-qty-price">
                             <?php echo $item['quantity']; ?> шт. × <?php echo number_format($item['price_per_item'], 2, ',', ' '); ?> руб.
                         </span>
                     </div>
                     <span class="item-subtotal">
                         = <?php echo number_format($item['price_per_item'] * $item['quantity'], 2, ',', ' '); ?> руб.
                     </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: // Если ошибки не было, но массив позиций пуст ?>
        <p>В этом заказе нет позиций (возможно, они были удалены).</p>
    <?php endif; ?>

</div>

<style>
/* Стили можно вынести в основной style.css или создать отдельный файл */
.order-details-view {
    background-color: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    margin-bottom: 20px;
    border-radius: 5px;
}
.order-info div {
    margin-bottom: 8px;
    line-height: 1.5;
}
.order-info strong {
    min-width: 150px; /* Выравнивание для заголовков */
    display: inline-block;
}
.total-sum {
    font-size: 1.1em;
    color: #e8491d;
}
.order-status-new { color: blue; font-weight: bold; }
.order-status-processing { color: orange; font-weight: bold; }
.order-status-delivering { color: purple; font-weight: bold; }
.order-status-completed { color: green; font-weight: bold; }
.order-status-cancelled { color: red; text-decoration: line-through; }

.order-items-list {
    list-style: none;
    padding: 0;
    margin-top: 15px;
}
.order-items-list li {
    display: flex;
    align-items: center;
    border-bottom: 1px dashed #eee;
    padding: 10px 0;
    flex-wrap: wrap; /* Для мобильных */
}
.order-items-list li:last-child {
    border-bottom: none;
}
.order-items-list img {
    width: 60px;
    height: 45px;
    object-fit: cover;
    margin-right: 15px;
    border: 1px solid #eee;
    border-radius: 3px;
}
.order-items-list .item-details {
    flex-grow: 1;
    margin-right: 10px;
}
.order-items-list .item-name {
    display: block;
    font-weight: bold;
    margin-bottom: 3px;
}
.order-items-list .item-qty-price {
    display: block;
    font-size: 0.9em;
    color: #555;
}
.order-items-list .item-subtotal {
    font-weight: bold;
    min-width: 90px; /* Выравнивание суммы */
    text-align: right;
    margin-left: auto; /* Прижимает вправо */
}
.button.secondary { background-color: #ccc; color: #333; }
.button.secondary:hover { background-color: #bbb; }
.button.small { padding: 3px 8px; font-size: 0.9em; }
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
// --- КОНЕЦ ФАЙЛА view_order.php ---
?>