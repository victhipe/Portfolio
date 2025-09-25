<?php
// Этот файл подключается ПОСЛЕ config.php, functions.php и проверки роли checkUserRole('operator')
// Он НЕ должен сам их подключать, это задача вызывающего скрипта (index.php, view_orders.php).
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape(SITE_NAME); ?> - <?php echo $page_title ?? 'Панель оператора'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin_style.css?v=<?php echo time(); ?>"> <!-- Используем те же стили -->
</head>
<body class="operator-panel"> <!-- Можно добавить класс для специфики, если нужно -->
    <header class="site-header operator-header"> <!-- Класс можно сделать operator-header -->
         <div class="container header-container">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>operator/"><?php echo escape(SITE_NAME); ?></a> (Панель оператора)
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>operator/">Новые заказы</a></li>
                    <li><a href="<?php echo BASE_URL; ?>operator/view_orders.php">Все заказы</a></li>
                    <li><a href="<?php echo BASE_URL; ?>" target="_blank">Посмотреть сайт</a></li> <!-- Ссылка на сам сайт -->
                    <li><a href="<?php echo BASE_URL; ?>logout.php">Выход (<?php echo escape($_SESSION['username'] ?? ''); ?>)</a></li>
                </ul>
            </nav>
        </div>
    </header>
     <main class="site-main operator-main"> <!-- Класс можно сделать operator-main -->
        <div class="container">
         <?php
            // Вывод flash-сообщений (стандартный блок)
            if (isset($_SESSION['flash_messages'])) {
                foreach ($_SESSION['flash_messages'] as $key => $message) {
                    echo '<div class="flash-message ' . escape($key) . '">' . escape($message) . '</div>';
                }
                unset($_SESSION['flash_messages']);
            }
         ?>