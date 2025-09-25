<?php
$page_title = "Панель администратора";
require_once __DIR__ . '/../includes/functions.php'; // Функции и сессии
checkUserRole('admin'); // Проверка, что пользователь - админ

require_once __DIR__ . '/_admin_header.php'; // Шапка админки
require_once __DIR__ . '/../includes/db_connect.php'; // Подключение к БД

// Здесь можно вывести какую-то сводную информацию

// Пример: Получение количества новых заказов
$sql_new_orders = "SELECT COUNT(*) as count FROM orders WHERE status = 'new'";
$result_new = safeQuery($sql_new_orders);
$new_orders_count = ($result_new && $row = $result_new->fetch_assoc()) ? $row['count'] : 0;
if($result_new) $result_new->free();

// --- ИСПРАВЛЕНИЕ ---
// Заменяем запрос к 'pizzas' на запрос к 'menu_items'
// Можно считать все пункты меню или только пиццы, если нужно. Посчитаем все.
$sql_total_items = "SELECT COUNT(*) as count FROM menu_items"; // Используем menu_items
$result_items = safeQuery($sql_total_items); // Новое имя переменной результата
// Новое имя переменной для количества
$total_menu_items_count = ($result_items && $row = $result_items->fetch_assoc()) ? $row['count'] : 0;
// Освобождаем новый результат
if($result_items) $result_items->free();
// --- КОНЕЦ ИСПРАВЛЕНИЯ ---

// Можно добавить подсчет пользователей (кроме админа)
$current_admin_id = getCurrentUserId();
$sql_users = "SELECT COUNT(*) as count FROM users WHERE role != 'admin'"; // Пример: считаем не-админов
$result_users = safeQuery($sql_users);
$customer_operator_count = ($result_users && $row = $result_users->fetch_assoc()) ? $row['count'] : 0;
if($result_users) $result_users->free();


?>

<h1>Добро пожаловать, Администратор!</h1>
<p>Это главная страница панели управления сайтом "Вкусная пицца!".</p>

<div class="dashboard-summary">
    <h2>Краткая сводка:</h2>
    <ul>
        <li><a href="<?php echo BASE_URL; ?>admin/manage_orders.php?status=new">Новых заказов: <?php echo $new_orders_count; ?></a></li>
        <?php /* --- ИСПРАВЛЕНИЕ ССЫЛКИ И ТЕКСТА --- */ ?>
        <li><a href="<?php echo BASE_URL; ?>admin/manage_menu.php">Всего позиций в меню: <?php echo $total_menu_items_count; ?></a></li>
        <?php /* --- КОНЕЦ ИСПРАВЛЕНИЯ --- */ ?>
        <li><a href="<?php echo BASE_URL; ?>admin/manage_users.php">Клиентов и операторов: <?php echo $customer_operator_count; ?></a></li>
        <?php /* Можно добавить еще данных */ ?>
    </ul>
</div>

<h2>Основные разделы:</h2>
<ul>
    <?php /* --- ИСПРАВЛЕНИЕ ССЫЛКИ И ТЕКСТА --- */ ?>
    <li><a href="<?php echo BASE_URL; ?>admin/manage_menu.php">Управление меню</a></li>
     <?php /* --- КОНЕЦ ИСПРАВЛЕНИЯ --- */ ?>
    <li><a href="<?php echo BASE_URL; ?>admin/manage_orders.php">Управление заказами</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/manage_users.php">Управление пользователями</a></li>
    <?php /* Добавить ссылки на другие функции админки */ ?>
</ul>


<?php
require_once __DIR__ . '/_admin_footer.php'; // Подвал админки
?>