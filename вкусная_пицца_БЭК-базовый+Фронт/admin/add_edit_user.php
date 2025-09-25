<?php
require_once __DIR__ . '/../includes/functions.php';
checkUserRole('admin');

require_once __DIR__ . '/../includes/db_connect.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$is_editing = ($user_id !== null);
$page_title = $is_editing ? "Редактирование пользователя" : "Добавление пользователя";

// Запрещаем редактировать самого себя через эту форму
if ($is_editing && $user_id === getCurrentUserId()) {
     setFlashMessage('error', 'Вы не можете редактировать свой профиль через эту форму. Используйте раздел "Профиль".');
     header('Location: ' . BASE_URL . 'admin/manage_users.php');
     exit;
}


$user = [
    'id' => null,
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone_number' => '',
    'address' => '',
    'role' => 'customer' // Роль по умолчанию при добавлении
];
$allowed_roles = ['customer', 'operator', 'admin']; // Доступные роли

if ($is_editing) {
    $sql = "SELECT id, username, email, full_name, phone_number, address, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();
        if ($user_data) {
            $user = $user_data;
        } else {
            setFlashMessage('error', 'Пользователь не найден.');
            header('Location: ' . BASE_URL . 'admin/manage_users.php');
            exit;
        }
    }
}

// Восстановление данных из сессии в случае ошибки валидации
if (isset($_SESSION['form_data'])) {
    $user = array_merge($user, $_SESSION['form_data']);
    unset($_SESSION['form_data']);
}

require_once __DIR__ . '/_admin_header.php';
?>

<h1><?php echo escape($page_title); ?></h1>

<?php
// Показать сообщения
$error_message = getFlashMessage('error');
if ($error_message) echo '<div class="flash-message error">' . $error_message . '</div>';
?>

<form action="<?php echo BASE_URL; ?>actions/admin_save_user.php" method="post">
    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

    <fieldset>
         <legend>Учетные данные</legend>
         <div>
             <label for="user_username">Имя пользователя (логин): <span class="required">*</span></label>
             <input type="text" id="user_username" name="username" value="<?php echo escape($user['username']); ?>" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+" <?php echo $is_editing ? 'disabled' : ''; // Запрещаем менять логин существующего ?>>
             <?php if ($is_editing): ?>
                 <input type="hidden" name="username" value="<?php echo escape($user['username']); ?>">
                 <small>Имя пользователя изменить нельзя.</small>
             <?php else: ?>
                  <small>Только латинские буквы, цифры и знак подчеркивания.</small>
             <?php endif; ?>
         </div>
         <div>
             <label for="user_email">Email: <span class="required">*</span></label>
             <input type="email" id="user_email" name="email" value="<?php echo escape($user['email']); ?>" required maxlength="100">
         </div>
          <div>
             <label for="user_role">Роль: <span class="required">*</span></label>
             <select id="user_role" name="role" required>
                 <?php foreach ($allowed_roles as $role): ?>
                     <option value="<?php echo $role; ?>" <?php echo ($user['role'] === $role) ? 'selected' : ''; ?>>
                         <?php echo escape(ucfirst($role)); ?>
                     </option>
                 <?php endforeach; ?>
             </select>
         </div>
    </fieldset>

    <fieldset>
         <legend>Личная информация</legend>
          <div>
             <label for="user_full_name">Полное имя:</label>
             <input type="text" id="user_full_name" name="full_name" value="<?php echo escape($user['full_name'] ?? ''); ?>" maxlength="100">
         </div>
          <div>
             <label for="user_phone">Телефон:</label>
             <input type="tel" id="user_phone" name="phone_number" value="<?php echo escape($user['phone_number'] ?? ''); ?>" pattern="^\+?[0-9\s\-\(\)]+$" maxlength="20">
              <small>Например: +7 (999) 123-45-67</small>
         </div>
         <div>
             <label for="user_address">Адрес:</label>
             <textarea id="user_address" name="address" rows="3"><?php echo escape($user['address'] ?? ''); ?></textarea>
         </div>
    </fieldset>

     <fieldset>
         <legend>Установка/Сброс пароля</legend>
         <div>
             <label for="user_password">Новый пароль:</label>
             <input type="password" id="user_password" name="password" minlength="6">
             <small><?php echo $is_editing ? 'Оставьте пустым, чтобы не менять текущий пароль.' : 'Пароль обязателен при создании пользователя.'; ?></small>
         </div>
          <div>
             <label for="user_password_confirm">Подтвердите пароль:</label>
             <input type="password" id="user_password_confirm" name="password_confirm" minlength="6">
         </div>
    </fieldset>

    <div>
        <button type="submit" class="button"><?php echo $is_editing ? 'Сохранить изменения' : 'Добавить пользователя'; ?></button>
        <a href="<?php echo BASE_URL; ?>admin/manage_users.php" class="button secondary">Отмена</a>
    </div>
</form>
<style>
    .required { color: red; }
    small { display: block; font-size: 0.8em; color: #666; margin-top: -5px; margin-bottom: 10px; }
    fieldset { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; }
    legend { font-weight: bold; padding: 0 5px; }
    .button.secondary { background-color: #ccc; color: #333; }
    .button.secondary:hover { background-color: #bbb; }
</style>

<?php
require_once __DIR__ . '/_admin_footer.php';
?>