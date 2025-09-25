<?php
$is_admin_view = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false); // Определяем, админ или оператор
$page_title = $is_admin_view ? "Управление заказами" : "Просмотр заказов";

require_once __DIR__ . '/../includes/functions.php';
// Проверка роли: Админ может все, оператор только свое
if ($is_admin_view) {
    checkUserRole('admin');
} else {
    checkUserRole('operator');
}

require_once __DIR__ . ($is_admin_view ? '/../admin/_admin_header.php' : '/_operator_header.php');
require_once __DIR__ . '/../includes/db_connect.php';

// Фильтрация и пагинация (очень упрощенно)
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
// Возможные статусы для фильтра
$allowed_statuses = ['new', 'processing', 'delivering', 'completed', 'cancelled'];

$where_clause = "";
$params = [];
$types = "";

if (!empty($filter_status) && in_array($filter_status, $allowed_statuses)) {
    $where_clause = " WHERE o.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// Получаем список заказов
$sql_orders = "SELECT o.id, o.order_time, o.customer_name, o.phone_number, o.total_price, o.status, u.username
               FROM orders o
               LEFT JOIN users u ON o.user_id = u.id"
             . $where_clause .
             " ORDER BY o.order_time DESC"; // Сначала новые

$result_orders = safeQuery($sql_orders, $params, $types);

// --- Обработка запроса на просмотр деталей одного заказа ---
$order_details = null;
$order_items = [];
$view_order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($view_order_id) {
    // Получаем основную информацию о заказе
    $sql_detail = "SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?";
    $stmt_detail = $conn->prepare($sql_detail);
    if($stmt_detail){
        $stmt_detail->bind_param("i", $view_order_id);
        $stmt_detail->execute();
        $result_detail = $stmt_detail->get_result();
        $order_details = $result_detail->fetch_assoc();
        $stmt_detail->close();
    }

    if ($order_details) {
        // Получаем позиции заказа
        $sql_items = "SELECT oi.quantity, oi.price_per_item, oi.size, p.name as item_name
                      FROM order_items oi
                      JOIN menu_items p ON oi.menu_item_id = p.id
                      WHERE oi.order_id = ?";
        $stmt_items = $conn->prepare($sql_items);
         if($stmt_items){
            $stmt_items->bind_param("i", $view_order_id);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();
            while ($item = $result_items->fetch_assoc()) {
                $order_items[] = $item; // Заполняем массив $order_items
            }
            $stmt_items->close();
        }
    } else {
        setFlashMessage('error', 'Заказ с ID ' . $view_order_id . ' не найден.');
        // Убираем ID из GET, чтобы не пытаться отобразить детали снова
        header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
}

?>

<h1><?php echo escape($page_title); ?></h1>

<?php
// Показать сообщения
$success_message = getFlashMessage('success');
if ($success_message) echo '<div class="flash-message success">' . escape($success_message) . '</div>';
$error_message = getFlashMessage('error');
if ($error_message) echo '<div class="flash-message error">' . escape($error_message) . '</div>';
?>

<!-- Фильтр по статусу -->
<div class="order-filter">
    <form method="get" action="">
        <label for="status_filter">Фильтр по статусу:</label>
        <select name="status" id="status_filter" onchange="this.form.submit()">
            <option value="">Все статусы</option>
            <?php foreach ($allowed_statuses as $status): ?>
                <option value="<?php echo $status; ?>" <?php echo ($filter_status === $status) ? 'selected' : ''; ?>>
                    <?php echo escape(ucfirst($status)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if($view_order_id) echo '<input type="hidden" name="id" value="'.$view_order_id.'">'; ?>
    </form>
</div>
<br>


<!-- Если смотрим детали одного заказа -->
<h3>Состав заказа:</h3>
        <?php if (!empty($order_items)): ?>
        <ul>
            <?php foreach ($order_items as $item): ?>
                <li>
                    <?php /* ОБНОВЛЕННЫЙ ВЫВОД НАЗВАНИЯ С РАЗМЕРОМ */ ?>
                    <?php echo escape($item['item_name']); ?><?php if(!empty($item['size'])) echo ' (' . escape($item['size']) . ' см)'; ?>
                     x <?php echo $item['quantity']; ?> шт.
                    (<?php echo number_format($item['price_per_item'], 2, ',', ' '); ?> руб./шт.)
                     = <?php echo number_format($item['price_per_item'] * $item['quantity'], 2, ',', ' '); ?> руб.
                </li>
            <?php endforeach; ?>
        </ul>
        <?php elseif($stmt_items) : // Проверяем, что stmt_items был инициализирован ?>
            <p>Не удалось загрузить состав заказа.</p>
        <?php endif; ?>

        <hr>
        <h3>Изменить статус заказа:</h3>
        <form action="<?php echo BASE_URL; ?>actions/operator_update_order_status.php" method="post">
             <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
             <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
             <select name="new_status" required>
                 <?php foreach ($allowed_statuses as $status): ?>
                 <option value="<?php echo $status; ?>" <?php echo ($order_details['status'] === $status) ? 'selected' : ''; ?>>
                     <?php echo escape(ucfirst($status)); ?>
                 </option>
                 <?php endforeach; ?>
             </select>
             <button type="submit" class="button">Обновить статус</button>
             <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); // Убрать ?id=... ?>" class="button secondary">Закрыть детали</a>
        </form>
    </div>
    <hr>



<!-- Общий список заказов -->
<h2><?php echo $view_order_id ? 'Все заказы (' . ($filter_status ? 'Статус: '.ucfirst($filter_status) : 'Все статусы') . ')' : 'Список заказов'; ?></h2>

<?php if ($result_orders && $result_orders->num_rows > 0): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Время</th>
                <th>Клиент</th>
                <th>Телефон</th>
                <th>Сумма</th>
                <th>Статус</th>
                 <?php if ($is_admin_view) echo "<th>Польз.</th>"; ?>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php while($order = $result_orders->fetch_assoc()): ?>
                <tr class="<?php echo ($view_order_id == $order['id']) ? 'highlighted' : ''; ?>">
                    <td><?php echo $order['id']; ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($order['order_time'])); ?></td>
                    <td><?php echo escape($order['customer_name']); ?></td>
                    <td><?php echo escape($order['phone_number']); ?></td>
                    <td><?php echo number_format($order['total_price'], 2, ',', ' '); ?> р.</td>
                    <td><?php echo escape(ucfirst($order['status'])); ?></td>
                     <?php if ($is_admin_view) echo "<td>" . escape($order['username'] ?: 'Гость') . "</td>"; ?>
                    <td class="actions">
                         <a href="?<?php echo http_build_query(array_merge($_GET, ['id' => $order['id']])); // Добавляем id к текущим GET параметрам ?>" class="button view">Детали</a>
                        <?php if ($is_admin_view): ?>
                            <!-- Админ может иметь доп. действия, например, удаление -->
                            <!-- <form action="..." method="post" onsubmit="...">...</form> -->
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Заказы<?php echo $filter_status ? ' со статусом "' . escape(ucfirst($filter_status)) . '"' : ''; ?> не найдены.</p>
<?php endif; ?>
<?php if($result_orders) $result_orders->free(); ?>

<style>
    .order-filter { margin-bottom: 15px; }
    .order-details-box { background: #f0f0f0; border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; }
    .admin-table tr.highlighted td { background-color: #fffacd; } /* Подсветка выбранного заказа */
    .button.view { background-color: #4CAF50; }
    .button.view:hover { background-color: #45a049; }
    .button.secondary { background-color: #ccc; color: #333; }
    .button.secondary:hover { background-color: #bbb; }
</style>


<?php
require_once __DIR__ . ($is_admin_view ? '/../admin/_admin_footer.php' : '/_operator_footer.php');
?>