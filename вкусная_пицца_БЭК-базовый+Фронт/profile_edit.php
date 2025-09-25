<?php
$page_title = "Редактирование профиля";
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

// Требуем авторизацию
if (!isLoggedIn()) {
    setFlashMessage('info', 'Пожалуйста, войдите, чтобы редактировать профиль.');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user_id = getCurrentUserId();

// Получаем текущие данные пользователя для формы
$user_data = null;
$sql_user = "SELECT username, email, full_name, phone_number, address FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
if($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_data = $result_user->fetch_assoc();
    $stmt_user->close();
}

if (!$user_data) {
     setFlashMessage('error', 'Не удалось загрузить данные пользователя.');
     header('Location: ' . BASE_URL . 'profile.php');
     exit;
}

// Восстановление данных из сессии в случае ошибки валидации
if (isset($_SESSION['form_data'])) {
    $user_data = array_merge($user_data, $_SESSION['form_data']);
    unset($_SESSION['form_data']);
}


require_once __DIR__ . '/includes/header.php';
?>

<h1><?php echo escape($page_title); ?></h1>

<?php
// Показать сообщения
$error_message = getFlashMessage('error');
if ($error_message) echo '<div class="flash-message error">' . $error_message . '</div>'; // Не экранируем, т.к. ошибки могут содержать <br>
$success_message = getFlashMessage('success');
if ($success_message) echo '<div class="flash-message success">' . escape($success_message) . '</div>';
?>

<form id="profile-edit-form" action="<?php echo BASE_URL; ?>actions/handle_profile_update.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

    <fieldset>
        <legend>Основная информация</legend>
        <div>
            <label for="prof_username">Имя пользователя (логин):</label>
            <input type="text" id="prof_username" value="<?php echo escape($user_data['username']); ?>" disabled>
             <small>Имя пользователя изменить нельзя.</small>
        </div>
        <div>
            <label for="prof_email">Email: <span class="required">*</span></label>
            <input type="email" id="prof_email" name="email" value="<?php echo escape($user_data['email']); ?>" required maxlength="100">
        </div>
        <div>
            <label for="prof_full_name">Полное имя:</label>
            <input type="text" id="prof_full_name" name="full_name" value="<?php echo escape($user_data['full_name'] ?? ''); ?>" maxlength="100">
        </div>
         <div>
            <label for="prof_phone">Телефон:</label>
            <input type="tel" id="prof_phone" name="phone_number" value="<?php echo escape($user_data['phone_number'] ?? ''); ?>" pattern="^\+?[0-9\s\-\(\)]+$" maxlength="20">
             <small>Например: +7 (999) 123-45-67</small>
        </div>
        <div>
            <label for="prof_address">Адрес доставки (по умолчанию):</label>
            <textarea id="prof_address" name="address" rows="3"><?php echo escape($user_data['address'] ?? ''); ?></textarea>
        </div>
    </fieldset>

    <fieldset>
         <legend>Изменение пароля (оставьте пустым, чтобы не менять)</legend>
         <div>
             <label for="prof_current_password">Текущий пароль:</label>
             <input type="password" id="prof_current_password" name="current_password">
             <small>Нужен для подтверждения смены email или установки нового пароля.</small>
         </div>
         <div>
             <label for="prof_new_password">Новый пароль:</label>
             <input type="password" id="prof_new_password" name="new_password" minlength="6">
         </div>
          <div>
             <label for="prof_confirm_password">Подтвердите новый пароль:</label>
             <input type="password" id="prof_confirm_password" name="confirm_password" minlength="6">
         </div>
    </fieldset>

    <div>
        <button type="submit" class="button">Сохранить изменения</button>
        <a href="<?php echo BASE_URL; ?>profile.php" class="button secondary">Отмена</a>
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
$extra_js = ['validation.js']; // Можно добавить специфичную валидацию
require_once __DIR__ . '/includes/footer.php';
?>