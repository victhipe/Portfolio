<?php
$page_title = "Управление пользователями";
require_once __DIR__ . '/../includes/functions.php';
checkUserRole('admin'); // Только админ

require_once __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Получаем список всех пользователей, кроме самого себя (чтобы админ себя случайно не удалил)
$current_admin_id = getCurrentUserId();
$sql = "SELECT id, username, email, full_name, role, created_at FROM users WHERE id != ? ORDER BY username ASC";
$result = safeQuery($sql, [$current_admin_id], "i");

?>

<h1><?php echo escape($page_title); ?></h1>

<p><a href="<?php echo BASE_URL; ?>admin/add_edit_user.php" class="button">Добавить нового пользователя</a></p>

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
                <th>Логин</th>
                <th>Email</th>
                <th>Полное имя</th>
                <th>Роль</th>
                <th>Дата регистрации</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php while($user = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo escape($user['username']); ?></td>
                    <td><?php echo escape($user['email']); ?></td>
                    <td><?php echo escape($user['full_name'] ?: '-'); ?></td>
                    <td><?php echo escape(ucfirst($user['role'])); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                    <td class="actions">
                        <a href="<?php echo BASE_URL; ?>admin/add_edit_user.php?id=<?php echo $user['id']; ?>" class="button edit">Ред.</a>
                        <form action="<?php echo BASE_URL; ?>actions/admin_delete_user.php" method="post" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить пользователя \'<?php echo escape(addslashes($user['username'])); ?>\'? Это действие необратимо!');">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <button type="submit" class="button delete">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Других пользователей не найдено.</p>
<?php endif; ?>
<?php if($result) $result->free(); ?>


<?php
require_once __DIR__ . '/_admin_footer.php';
?>