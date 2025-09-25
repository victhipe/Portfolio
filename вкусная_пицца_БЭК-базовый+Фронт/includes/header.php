<?php require_once __DIR__ . '/../config.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape(SITE_NAME); ?> - <?php echo $page_title ?? 'Главная'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css?v=<?php echo time(); // Для сброса кэша при разработке ?>">
    <!-- Дополнительные стили для конкретной страницы -->
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo BASE_URL . 'css/' . $css_file . '?v=' . time(); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container header-container">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>"><?php echo escape(SITE_NAME); ?></a>
                <!-- Можно добавить <img src="<?php echo BASE_URL; ?>images/logo.png" alt="Лого"> -->
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>">Меню</a></li>
                    <li><a href="<?php echo BASE_URL; ?>cart.php">Корзина</a></li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (getCurrentUserRole() === 'admin'): ?>
                            <li><a href="<?php echo BASE_URL; ?>admin/">Админ</a></li>
                        <?php elseif (getCurrentUserRole() === 'operator'): ?>
                            <li><a href="<?php echo BASE_URL; ?>operator/">Оператор</a></li>
                        <?php else: ?>
                             <li><a href="<?php echo BASE_URL; ?>profile.php">Мои заказы</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>logout.php">Выход (<?php echo escape($_SESSION['username'] ?? ''); ?>)</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>login.php">Вход</a></li>
                        <li><a href="<?php echo BASE_URL; ?>register.php">Регистрация</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="site-main">
        <div class="container">
            <?php
            // Вывод flash-сообщений
            if (isset($_SESSION['flash_messages'])) {
                foreach ($_SESSION['flash_messages'] as $key => $message) {
                    echo '<div class="flash-message ' . escape($key) . '">' . escape($message) . '</div>';
                }
                unset($_SESSION['flash_messages']); // Очистить после показа
            }
            ?>