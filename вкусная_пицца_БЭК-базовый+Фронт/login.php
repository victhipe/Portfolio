<?php
$page_title = "Вход";
require_once __DIR__ . '/includes/header.php'; // Заголовок включает config и functions

// Если пользователь уже вошел, перенаправляем его
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . 'admin/');
    } elseif ($role === 'operator') {
         header('Location: ' . BASE_URL . 'operator/');
    } else {
        header('Location: ' . BASE_URL . 'profile.php');
    }
    exit;
}
?>

<h1><?php echo escape($page_title); ?></h1>

<?php
// Показать сообщение об ошибке, если оно есть
$error_message = getFlashMessage('error');
if ($error_message) {
    echo '<div class="flash-message error">' . escape($error_message) . '</div>';
}
$success_message = getFlashMessage('success');
if ($success_message) {
    echo '<div class="flash-message success">' . escape($success_message) . '</div>';
}
?>


<form action="<?php echo BASE_URL; ?>actions/handle_login.php" method="post">
     <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <div>
        <label for="login_username">Имя пользователя или Email:</label>
        <input type="text" id="login_username" name="username_or_email" required>
    </div>
    <div>
        <label for="login_password">Пароль:</label>
        <input type="password" id="login_password" name="password" required>
    </div>
    <div>
        <button type="submit">Войти</button>
    </div>
</form>

<p>Еще нет аккаунта? <a href="<?php echo BASE_URL; ?>register.php">Зарегистрироваться</a></p>

<?php
require_once __DIR__ . '/includes/footer.php';
?>