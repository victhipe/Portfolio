<?php
$page_title = "Регистрация";
require_once __DIR__ . '/includes/header.php'; // Включает config и functions

// Если пользователь уже вошел, перенаправляем его
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'profile.php');
    exit;
}
?>

<h1><?php echo escape($page_title); ?></h1>

<?php
// Показать сообщение об ошибке/успехе, если оно есть
$error_message = getFlashMessage('error');
if ($error_message) {
    echo '<div class="flash-message error">' . escape($error_message) . '</div>';
}
$success_message = getFlashMessage('success');
if ($success_message) {
    echo '<div class="flash-message success">' . escape($success_message) . '</div>';
}
?>

<form id="register-form" action="<?php echo BASE_URL; ?>actions/handle_register.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <div>
        <label for="reg_username">Имя пользователя (логин): <span class="required">*</span></label>
        <input type="text" id="reg_username" name="username" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+">
        <small>Только латинские буквы, цифры и знак подчеркивания.</small>
    </div>
    <div>
        <label for="reg_email">Email: <span class="required">*</span></label>
        <input type="email" id="reg_email" name="email" required maxlength="100">
    </div>
    <div>
        <label for="reg_password">Пароль: <span class="required">*</span></label>
        <input type="password" id="reg_password" name="password" required minlength="6">
    </div>
     <div>
        <label for="reg_password_confirm">Подтвердите пароль: <span class="required">*</span></label>
        <input type="password" id="reg_password_confirm" name="password_confirm" required minlength="6">
    </div>
    <div>
        <label for="reg_full_name">Полное имя:</label>
        <input type="text" id="reg_full_name" name="full_name" maxlength="100">
    </div>
     <div>
        <label for="reg_phone">Телефон:</label>
        <input type="tel" id="reg_phone" name="phone_number" pattern="^\+?[0-9\s\-\(\)]+$" maxlength="20">
         <small>Например: +7 (999) 123-45-67</small>
    </div>
    <div>
        <label for="reg_address">Адрес доставки (по умолчанию):</label>
        <textarea id="reg_address" name="address" rows="3"></textarea>
    </div>
    <div>
        <button type="submit">Зарегистрироваться</button>
    </div>
</form>

<style>
    .required { color: red; }
    small { display: block; font-size: 0.8em; color: #666; margin-top: -5px; margin-bottom: 10px; }
</style>

<?php
// Подключаем JS для валидации, если он есть
$extra_js = ['validation.js'];
require_once __DIR__ . '/includes/footer.php';
?>