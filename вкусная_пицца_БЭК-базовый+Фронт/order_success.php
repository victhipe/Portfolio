<?php
$page_title = "Заказ успешно оформлен!";
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

// Получаем ID последнего заказа из сессии
$last_order_id = $_SESSION['last_order_id'] ?? null;
// Очищаем его из сессии, чтобы не показывать снова при обновлении страницы
unset($_SESSION['last_order_id']);

require_once __DIR__ . '/includes/header.php';
?>

<h1><?php echo escape($page_title); ?></h1>

<?php if ($last_order_id): ?>
    <p>Спасибо за ваш заказ! Ваш номер заказа: <strong><?php echo $last_order_id; ?></strong>.</p>
    <p>Наш оператор свяжется с вами в ближайшее время для подтверждения деталей.</p>
    <p>Вы можете отслеживать статус заказа в <a href="<?php echo BASE_URL; ?>profile.php">вашем профиле</a>.</p>
<?php else: ?>
    <p>Спасибо за ваш заказ! Наш оператор скоро свяжется с вами.</p>
    <p>Вы можете отслеживать статус ваших заказов в <a href="<?php echo BASE_URL; ?>profile.php">вашем профиле</a>.</p>
<?php endif; ?>

<p><a href="<?php echo BASE_URL; ?>" class="button">Вернуться в меню</a></p>

<?php
require_once __DIR__ . '/includes/footer.php';
?>