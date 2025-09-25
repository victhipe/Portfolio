<?php
// --- НАЧАЛО ФАЙЛА admin/manage_orders.php ---
$page_title = "Управление заказами";
require_once __DIR__ . '/../includes/functions.php'; // Функции, сессии, CSRF
checkUserRole('admin'); // Проверка роли администратора

require_once __DIR__ . '/_admin_header.php'; // Шапка админки
require_once __DIR__ . '/../includes/db_connect.php'; // Подключение к БД

// --- Фильтрация и пагинация (пагинация пока не реализована) ---
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
// Возможные статусы для фильтра (можно взять из ENUM схемы)
$allowed_statuses = ['new', 'processing', 'delivering', 'completed', 'cancelled'];

$where_clause = "";
$params = [];
$types = "";

if (!empty($filter_status) && in_array($filter_status, $allowed_statuses)) {
    $where_clause = " WHERE o.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// --- Получение списка заказов ---
$sql_orders_list = "SELECT o.id, o.order_time, o.customer_name, o.phone_number, o.total_price, o.status, u.username
                   FROM orders o
                   LEFT JOIN users u ON o.user_id = u.id"
                  . $where_clause .
                  " ORDER BY o.order_time DESC"; // Сначала новые

// Используем $conn->prepare для безопасности, даже если параметры могут быть пустыми
$stmt_orders_list = $conn->prepare($sql_orders_list);
$orders_list = []; // Массив для хранения списка заказов
$list_fetch_error = false; // Флаг ошибки получения списка

if ($stmt_orders_list) {
    if (!empty($params)) {
        $stmt_orders_list->bind_param($types, ...$params);
    }
    if ($stmt_orders_list->execute()) {
        $result_orders_list = $stmt_orders_list->get_result();
        while ($order_row = $result_orders_list->fetch_assoc()) {
            $orders_list[] = $order_row;
        }
    } else {
        error_log("Ошибка выполнения запроса списка заказов: " . $stmt_orders_list->error);
        $list_fetch_error = true;
    }
    $stmt_orders_list->close();
} else {
    error_log("Ошибка подготовки запроса списка заказов: " . $conn->error);
    $list_fetch_error = true;
}


// --- Обработка запроса на просмотр деталей одного заказа ---
$order_details = null; // Данные конкретного заказа
$order_items = []; // Позиции конкретного заказа
$view_order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$details_fetch_error = false; // Флаг ошибки получения деталей
$items_fetch_error = false; // Флаг ошибки получения позиций

if ($view_order_id) {
    // Получаем основную информацию о заказе (админ может видеть любой заказ)
    $sql_detail = "SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?";
    $stmt_detail = $conn->prepare($sql_detail);
    if($stmt_detail){
        $stmt_detail->bind_param("i", $view_order_id);
        if ($stmt_detail->execute()) {
            $result_detail = $stmt_detail->get_result();
            $order_details = $result_detail->fetch_assoc(); // Получаем данные заказа
            if (!$order_details) { // Если заказ с таким ID не найден
                 setFlashMessage('error', 'Заказ с ID ' . $view_order_id . ' не найден.');
                 // Редирект на страницу без ?id=... чтобы убрать неверный параметр
                 header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
                 exit;
            }
        } else {
            error_log("Ошибка выполнения запроса деталей заказа {$view_order_id}: " . $stmt_detail->error);
            $details_fetch_error = true;
        }
        $stmt_detail->close();
    } else {
        error_log("Ошибка подготовки запроса деталей заказа {$view_order_id}: " . $conn->error);
        $details_fetch_error = true;
    }

    // Если детали заказа успешно получены, получаем его позиции
    if ($order_details && !$details_fetch_error) {
        $sql_items = "SELECT oi.quantity, oi.price_per_item, oi.size, p.name as item_name
                      FROM order_items oi
                      JOIN menu_items p ON oi.menu_item_id = p.id
                      WHERE oi.order_id = ?";
        $stmt_items = $conn->prepare($sql_items);
         if($stmt_items){
            $stmt_items->bind_param("i", $view_order_id);
            if ($stmt_items->execute()) {
                 $result_items = $stmt_items->get_result();
                 while ($item = $result_items->fetch_assoc()) {
                     $order_items[] = $item; // Заполняем массив позиций
                 }
            } else {
                 error_log("Ошибка выполнения запроса позиций заказа {$view_order_id}: " . $stmt_items->error);
                 $items_fetch_error = true;
            }
            $stmt_items->close();
        } else {
             error_log("Ошибка подготовки запроса позиций заказа {$view_order_id}: " . $conn->error);
             $items_fetch_error = true;
        }
    }
}

// --- НАЧАЛО ВЫВОДА HTML ---
?>

<h1><?php echo escape($page_title); ?></h1>

<?php
// Показать Flash сообщения (успех, ошибка, инфо)
$success_message = getFlashMessage('success');
if ($success_message) echo '<div class="flash-message success">' . escape($success_message) . '</div>';
$error_message = getFlashMessage('error');
if ($error_message) echo '<div class="flash-message error">' . escape($error_message) . '</div>';
$info_message = getFlashMessage('info');
if ($info_message) echo '<div class="flash-message info">' . escape($info_message) . '</div>';
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
        <?php // Если смотрим детали, сохраняем ID в скрытом поле при смене фильтра ?>
        <?php if($view_order_id) echo '<input type="hidden" name="id" value="'.$view_order_id.'">'; ?>
        <noscript><button type="submit" class="button small">Фильтровать</button></noscript>
    </form>
</div>
<br>


<!-- Секция деталей заказа (если выбран ID) -->
<?php if ($view_order_id && $order_details && !$details_fetch_error): ?>
    <div class="order-details-box">
        <h2>Детали заказа #<?php echo $order_details['id']; ?></h2>
        <div class="order-info">
            <div><strong>Статус:</strong> <span class="order-status-<?php echo escape($order_details['status']); ?>"><?php echo escape(ucfirst($order_details['status'])); ?></span></div>
            <div><strong>Время заказа:</strong> <?php echo date('d.m.Y H:i', strtotime($order_details['order_time'])); ?></div>
            <div><strong>Клиент:</strong> <?php echo escape($order_details['customer_name']); ?></div>
            <div><strong>Пользователь:</strong> <?php echo $order_details['user_id'] ? ('<a href="'.BASE_URL.'admin/manage_users.php?edit_id='.$order_details['user_id'].'">' . escape($order_details['username']) . '</a>') : 'Гость'; ?></div>
            <div><strong>Телефон:</strong> <?php echo escape($order_details['phone_number']); ?></div>
            <div><strong>Адрес доставки:</strong> <?php echo nl2br(escape($order_details['delivery_address'])); ?></div>
            <div><strong>Комментарий клиента:</strong> <?php echo nl2br(escape($order_details['operator_notes'] ?: 'Нет')); ?></div>
            <div><strong>Итоговая сумма:</strong> <strong class="total-sum"><?php echo number_format($order_details['total_price'], 2, ',', ' '); ?> руб.</strong></div>
        </div>

        <hr>
        <h3>Состав заказа:</h3>
        <?php if ($items_fetch_error): ?>
             <p class="flash-message error">Не удалось загрузить состав заказа.</p>
        <?php elseif (!empty($order_items)): ?>
            <ul class="order-items-list-admin">
                <?php foreach ($order_items as $item): ?>
                    <li>
                        <?php // Вывод названия с размером для пиццы ?>
                        <span class="item-name-admin">
                            <?php echo escape($item['item_name']); ?>
                            <?php if(!empty($item['size'])) echo ' (' . escape($item['size']) . ' см)'; ?>
                        </span>
                        <span class="item-qty-price-admin">
                             × <?php echo $item['quantity']; ?> шт.
                             (<?php echo number_format($item['price_per_item'], 2, ',', ' '); ?> руб./шт.)
                        </span>
                        <span class="item-subtotal-admin">
                             = <?php echo number_format($item['price_per_item'] * $item['quantity'], 2, ',', ' '); ?> руб.
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Состав заказа пуст или не найден.</p>
        <?php endif; ?>

        <hr>
        <h3>Изменить статус заказа:</h3>
        <form action="<?php echo BASE_URL; ?>actions/operator_update_order_status.php" method="post" class="update-status-form">
             <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
             <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
             <?php // Передаем текущий фильтр статуса, чтобы вернуться к нему ?>
             <?php if ($filter_status) echo '<input type="hidden" name="current_filter_status" value="'.escape($filter_status).'">'; ?>
             <select name="new_status" required>
                 <?php foreach ($allowed_statuses as $status): ?>
                 <option value="<?php echo $status; ?>" <?php echo ($order_details['status'] === $status) ? 'selected' : ''; ?>>
                     <?php echo escape(ucfirst($status)); ?>
                 </option>
                 <?php endforeach; ?>
             </select>
             <button type="submit" class="button">Обновить статус</button>
             <?php // Ссылка для закрытия деталей, сохраняя фильтр ?>
             <a href="?<?php if($filter_status) echo 'status='.urlencode($filter_status); ?>" class="button secondary">Скрыть детали</a>
        </form>
        <?php /* Здесь можно добавить кнопку/форму для удаления заказа (только для админа)
         <form action="<?php echo BASE_URL; ?>actions/admin_delete_order.php" method="post" onsubmit="return confirm('Уверены?');" style="display: inline-block; margin-left: 10px;">
              <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
              <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
              <button type="submit" class="button delete small">Удалить заказ</button>
         </form>
        */ ?>
    </div>
    <hr>
<?php elseif ($view_order_id && $details_fetch_error): ?>
     <p class="flash-message error">Не удалось загрузить детали заказа #<?php echo $view_order_id; ?>.</p>
     <p><a href="?<?php if($filter_status) echo 'status='.urlencode($filter_status); ?>" class="button secondary">Вернуться к списку</a></p>
     <hr>
<?php endif; ?>


<!-- Общий список заказов -->
<h2><?php echo $view_order_id ? 'Все заказы (' . ($filter_status ? 'Статус: '.ucfirst($filter_status) : 'Все статусы') . ')' : 'Список заказов'; ?></h2>

<?php if ($list_fetch_error): ?>
     <p class="flash-message error">Не удалось загрузить список заказов.</p>
<?php elseif (!empty($orders_list)): ?>
    <div class="table-responsive"> <?php // Обертка для таблиц на мал. экранах ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Время</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Польз.</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders_list as $order): ?>
                    <?php // Подсветка строки, если смотрим ее детали ?>
                    <tr class="<?php echo ($view_order_id == $order['id']) ? 'highlighted' : ''; ?>">
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($order['order_time'])); ?></td>
                        <td><?php echo escape($order['customer_name']); ?></td>
                        <td><?php echo escape($order['phone_number']); ?></td>
                        <td><?php echo number_format($order['total_price'], 2, ',', ' '); ?> р.</td>
                        <td><span class="order-status-<?php echo escape($order['status']); ?>"><?php echo escape(ucfirst($order['status'])); ?></span></td>
                        <td><?php echo escape($order['username'] ?: 'Гость'); ?></td>
                        <td class="actions">
                             <?php // Ссылка для просмотра деталей, сохраняя текущий фильтр ?>
                             <a href="?id=<?php echo $order['id']; ?><?php if($filter_status) echo '&status='.urlencode($filter_status); ?>" class="button view small">Детали</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>Заказы<?php echo $filter_status ? ' со статусом "' . escape(ucfirst($filter_status)) . '"' : ''; ?> не найдены.</p>
<?php endif; ?>

<style>
    /* Стили лучше вынести в admin_style.css */
    .order-filter { margin-bottom: 15px; background: #f9f9f9; padding: 10px; border: 1px solid #eee; border-radius: 4px; display: inline-block;}
    .order-filter label { margin-right: 5px; font-weight: bold;}
    .order-filter select { padding: 5px; border-radius: 3px; border: 1px solid #ccc;}

    .order-details-box { background: #f0f0f0; border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .order-details-box h2, .order-details-box h3 { margin-top: 0; }
    .order-details-box .order-info div { margin-bottom: 5px; line-height: 1.5; }
    .order-details-box .order-info strong { min-width: 120px; display: inline-block; color: #555;}
    .order-details-box .total-sum { font-size: 1.1em; color: #e8491d; }

    .order-items-list-admin { list-style: none; padding: 0; margin: 10px 0; }
    .order-items-list-admin li { border-bottom: 1px dotted #ccc; padding: 5px 0; display: flex; justify-content: space-between; flex-wrap: wrap;}
    .order-items-list-admin li:last-child { border-bottom: none; }
    .item-name-admin { font-weight: bold; flex-basis: 50%;} /* Занимает ~половину */
    .item-qty-price-admin { color: #666; font-size: 0.9em; flex-basis: 30%; text-align: right;}
    .item-subtotal-admin { font-weight: bold; flex-basis: 15%; text-align: right;}

    .update-status-form select { margin-right: 10px; padding: 6px; }

    .admin-table tr.highlighted td { background-color: #fffacd; } /* Подсветка выбранного заказа */
    .admin-table .actions a, .admin-table .actions button { margin-right: 3px; margin-bottom: 3px; } /* Отступы между кнопками */
    .button.view { background-color: #4CAF50; }
    .button.view:hover { background-color: #45a049; }
    .button.secondary { background-color: #ccc; color: #333; }
    .button.secondary:hover { background-color: #bbb; }
    .button.small { padding: 3px 8px; font-size: 0.9em; }
    .button.delete { background-color: #f44336; }
    .button.delete:hover { background-color: #da190b; }

    .table-responsive { overflow-x: auto; /* Горизонтальная прокрутка для таблицы на маленьких экранах */}

    /* Статусы заказов */
    .order-status-new { color: blue; }
    .order-status-processing { color: orange; }
    .order-status-delivering { color: purple; }
    .order-status-completed { color: green; }
    .order-status-cancelled { color: red; text-decoration: line-through; }
</style>

<?php
// --- НАЧАЛО ФУТЕРА ---
require_once __DIR__ . '/_admin_footer.php';
// --- КОНЕЦ ФАЙЛА admin/manage_orders.php ---
?>