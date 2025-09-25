<?php
$page_title = "Управление категориями";
require_once __DIR__ . '/../includes/functions.php';
checkUserRole('admin');

require_once __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Получаем список всех категорий
$sql = "SELECT id, name, slug, description, sort_order FROM categories ORDER BY sort_order ASC, name ASC";
$result = safeQuery($sql);

?>

<h1><?php echo escape($page_title); ?></h1>

<p><a href="<?php echo BASE_URL; ?>admin/add_edit_category.php" class="button">Добавить новую категорию</a></p>

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
                <th>Название</th>
                <th>Slug (URL)</th>
                <th>Описание (кратко)</th>
                <th>Порядок</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php while($category = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $category['id']; ?></td>
                    <td><?php echo escape($category['name']); ?></td>
                    <td><?php echo escape($category['slug']); ?></td>
                    <td><?php echo escape(mb_substr($category['description'] ?? '', 0, 50)) . (mb_strlen($category['description'] ?? '') > 50 ? '...' : ''); ?></td>
                    <td><?php echo $category['sort_order']; ?></td>
                    <td class="actions">
                        <a href="<?php echo BASE_URL; ?>admin/add_edit_category.php?id=<?php echo $category['id']; ?>" class="button edit">Ред.</a>
                        <form action="<?php echo BASE_URL; ?>actions/admin_delete_category.php" method="post" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить категорию \'<?php echo escape(addslashes($category['name'])); ?>\'? Это действие НЕВОЗМОЖНО, если в категории есть товары!');">
                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <button type="submit" class="button delete">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Категории пока не созданы.</p>
<?php endif; ?>
<?php if($result) $result->free(); ?>


<?php
require_once __DIR__ . '/_admin_footer.php';
?>