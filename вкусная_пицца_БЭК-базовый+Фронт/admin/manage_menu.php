<?php
// --- НАЧАЛО ФАЙЛА ---
$page_title = "Управление меню"; // Новый заголовок
require_once __DIR__ . '/../includes/functions.php';
checkUserRole('admin');

require_once __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Доступные категории для фильтра
$allowed_categories = ['pizza', 'drink', 'salad', 'snack', 'dessert'];
$category_names_map = [ /* ... как в add_edit_menu_item.php ... */
    'pizza' => 'Пицца', 'drink' => 'Напиток', 'salad' => 'Салат',
    'snack' => 'Закуска', 'dessert' => 'Десерт'
];

// Фильтрация
$filter_category = isset($_GET['filter_category']) ? trim($_GET['filter_category']) : '';
$where_clause = "";
$params = [];
$types = "";

if (!empty($filter_category) && in_array($filter_category, $allowed_categories)) {
    $where_clause = " WHERE category = ?";
    $params[] = $filter_category;
    $types .= "s";
}

// Получаем список пунктов меню с учетом фильтра
// Используем menu_items
$sql = "SELECT id, category, name, price, is_available, image_path FROM menu_items"
       . $where_clause .
       " ORDER BY category, name ASC";
$result = safeQuery($sql, $params, $types);

// --- КОНЕЦ ШАПКИ ---
?>

<h1><?php echo escape($page_title); ?></h1>

<!-- Ссылка на новую форму добавления -->
<p><a href="<?php echo BASE_URL; ?>admin/add_edit_menu_item.php" class="button">Добавить пункт меню</a></p>

<!-- Форма фильтрации -->
<form method="get" action="" class="filter-form">
    <label for="filter_category">Фильтр по категории:</label>
    <select name="filter_category" id="filter_category" onchange="this.form.submit()">
        <option value="">-- Все категории --</option>
        <?php foreach ($allowed_categories as $cat_key): ?>
            <option value="<?php echo $cat_key; ?>" <?php echo ($filter_category === $cat_key) ? 'selected' : ''; ?>>
                <?php echo escape($category_names_map[$cat_key] ?? ucfirst($cat_key)); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <noscript><button type="submit">Применить</button></noscript>
</form>


<?php
// Показать сообщения
$success_message = getFlashMessage('success');
if ($success_message) echo '<div class="flash-message success">' . escape($success_message) . '</div>';
$error_message = getFlashMessage('error');
if ($error_message) echo '<div class="flash-message error">' . escape($error_message) . '</div>';
?>

<?php if ($result && $result->num_rows > 0): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Категория</th> <!-- Новая колонка -->
                <th>Изображение</th>
                <th>Название</th>
                <th>Цена (баз.)</th>
                <th>Доступность</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php while($item = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <!-- Вывод категории -->
                    <td><?php echo escape($category_names_map[$item['category']] ?? ucfirst($item['category'])); ?></td>
                    <td>
                        <?php /* Логика отображения картинки осталась прежней */
                        $img_src = BASE_URL . 'images/placeholder.png';
                        if (!empty($item['image_path']) && file_exists(__DIR__ . '/../images/' . $item['image_path'])) {
                             $img_src = BASE_URL . 'images/' . escape($item['image_path']) . '?v=' . time();
                        }
                        ?>
                        <img src="<?php echo $img_src; ?>" alt="<?php echo escape($item['name']); ?>" style="max-width: 60px; max-height: 40px;">
                    </td>
                    <td><?php echo escape($item['name']); ?></td>
                    <!-- Цена теперь базовая -->
                    <td><?php echo number_format($item['price'], 2, ',', ' '); ?> руб.</td>
                    <td><?php echo $item['is_available'] ? 'Да' : 'Нет'; ?></td>
                    <td class="actions">
                        <!-- Ссылка на новую форму редактирования -->
                        <a href="<?php echo BASE_URL; ?>admin/add_edit_menu_item.php?id=<?php echo $item['id']; ?>" class="button edit">Ред.</a>
                        <!-- Форма удаления вызывает новый обработчик -->
                        <form action="<?php echo BASE_URL; ?>actions/admin_delete_menu_item.php" method="post" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить \'<?php echo escape(addslashes($item['name'])); ?>\'?');">
                            <!-- ID элемента теперь item_id -->
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <button type="submit" class="button delete">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>В меню<?php echo $filter_category ? ' категории "' . escape($category_names_map[$filter_category] ?? $filter_category) . '"' : ''; ?> пока нет позиций.</p>
<?php endif; ?>
<?php if($result) $result->free(); ?>

<style> /* Стили можно вынести в admin_style.css */
.filter-form { margin-bottom: 15px; }
.filter-form label { margin-right: 5px; }
.filter-form select { padding: 5px; }
</style>

<?php
// --- НАЧАЛО ФУТЕРА ---
require_once __DIR__ . '/_admin_footer.php';
// --- КОНЕЦ ФАЙЛА ---
?>