<?php
// --- Конфигурация и Функции ---
require_once __DIR__ . '/../includes/functions.php'; // Включает config.php внутри
// --- Проверка Роли ---
checkUserRole('operator');

// --- Установка Заголовка Страницы ---
$page_title = "Панель оператора - Новые заказы";

// --- Включение Шапки ---
require_once __DIR__ . '/_operator_header.php'; // Подключаем шапку оператора

// --- Подключение к БД ---
require_once __DIR__ . '/../includes/db_connect.php';

// --- Получение Данных ---
// Выбираем заказы со статусом 'new' или 'processing', сортируем по времени (сначала самые старые новые)
$sql_orders = "SELECT o.id, o.order_time, o.customer_name, o.phone_number, o.total_price, o.status
               FROM orders o
               WHERE o.status = 'new' OR o.status = 'processing'
               ORDER BY o.status ASC, o.order_time ASC"; // Сначала 'new', потом 'processing', внутри них по времени

$result_orders = safeQuery($sql_orders);

?>

<h1>Новые и обрабатываемые заказы</h1>
<p>Здесь отображаются заказы, требующие вашего внимания.</p>

<?php
// Показать сообщения (если были ошибки или информация при перенаправлении сюда)
$success_message = getFlashMessage('success');
if ($success_message) echo '<div class="flash-message success">' . escape($success_message) . '</div>';
$error_message = getFlashMessage('error');
if ($error_message) echo '<div class="flash-message error">' . escape($error_message) . '</div>';
?>


<?php if ($result_orders && $result_orders->num_rows > 0): ?>
    <table class="admin-table"> <!-- Используем стили админской таблицы -->
        <thead>
            <tr>
                <th>ID Заказа</th>
                <th>Время заказа</th>
                <th>Имя клиента</th>
                <th>Телефон</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php while($order = $result_orders->fetch_assoc()): ?>
                <tr class="<?php echo $order['status'] === 'new' ? 'status-new' : 'status-processing'; ?>"> <!-- Добавим классы для стилизации статусов -->
                    <td><?php echo $order['id']; ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($order['order_time'])); ?></td>
                    <td><?php echo escape($order['customer_name']); ?></td>
                    <td><?php echo escape($order['phone_number']); ?></td>
                    <td><?php echo number_format($order['total_price'], 2, ',', ' '); ?> руб.</td>
                    <td>
                        <span class="status-badge status-<?php echo escape($order['status']); ?>">
                            <?php echo escape(ucfirst($order['status'])); ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="<?php echo BASE_URL; ?>operator/view_orders.php?id=<?php echo $order['id']; ?>" class="button view small">Детали</a>
                        <!-- Сюда можно добавить кнопки для быстрой смены статуса (например, "Принять в обработку"), если нужно -->
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>В данный момент нет новых или обрабатываемых заказов.</p>
<?php endif; ?>
<?php if($result_orders) $result_orders->free(); // Освобождаем память ?>

<style>
    /* Дополнительные стили для статусов (можно вынести в admin_style.css) */
    .status-badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.85em;
        color: #fff;
        text-transform: lowercase;
    }
    .status-new { background-color: #dc3545; /* Красный */ }
    .status-processing { background-color: #ffc107; color: #333; /* Желтый */ }
    .status-delivering { background-color: #17a2b8; /* Голубой */ }
    .status-completed { background-color: #28a745; /* Зеленый */ }
    .status-cancelled { background-color: #6c757d; /* Серый */ }

    /* Можно подсветить строки с новыми заказами */
    /* tr.status-new td { background-color: #f8d7da; } */
</style>


<?php
// --- Включение Подвала ---
require_once __DIR__ . '/_operator_footer.php'; // Подключаем подвал оператора
?>