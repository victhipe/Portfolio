<?php
// Этот файл уже должен быть подключен после config.php и functions.php
// и проверки роли checkUserRole() или checkAdminOrOperator()
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape(SITE_NAME); ?> - <?php echo $page_title ?? 'Панель управления'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body class="admin-panel"> <!-- Добавим класс для специфичных стилей -->
    <header class="site-header admin-header">
         <div class="container header-container">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>"><?php echo escape(SITE_NAME); ?></a> (Панель управления)
            </div>
            <nav class="main-nav">
                <ul>
                    <?php if (getCurrentUserRole() === 'admin'): ?>
                        <li><a href="<?php echo BASE_URL; ?>admin/">Главная Админ</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/manage_menu.php">Позиции</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/manage_orders.php">Заказы</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/manage_users.php">Пользователи</a></li>
                    <?php elseif (getCurrentUserRole() === 'operator'): ?>
                         <li><a href="<?php echo BASE_URL; ?>operator/">Главная Оператор</a></li>
                         <li><a href="<?php echo BASE_URL; ?>operator/view_orders.php">Заказы</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo BASE_URL; ?>" target="_blank">Сайт</a></li> <!-- Ссылка на сам сайт -->
                    <li><a href="<?php echo BASE_URL; ?>logout.php">Выход (<?php echo escape($_SESSION['username'] ?? ''); ?>)</a></li>
                </ul>
            </nav>
        </div>
    </header>
     <main class="site-main admin-main">
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