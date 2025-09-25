<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверка роли - доступно админу и оператору
checkAdminOrOperator();

$is_admin_request = (getCurrentUserRole() === 'admin');
$base_redirect_url = BASE_URL . ($is_admin_request ? 'admin/manage_orders.php' : 'operator/view_orders.php');

// Проверка CSRF токена
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . $base_redirect_url);
     exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
$new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

// Возможные статусы
$allowed_statuses = ['new', 'processing', 'delivering', 'completed', 'cancelled'];

if ($order_id && !empty($new_status) && in_array($new_status, $allowed_statuses)) {

    // Обновляем статус в БД
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
             if ($stmt->affected_rows > 0) {
                setFlashMessage('success', 'Статус заказа #' . $order_id . ' успешно обновлен на "' . ucfirst($new_status) . '".');
             } else {
                 setFlashMessage('info', 'Статус заказа #' . $order_id . ' не был изменен (возможно, он уже был таким или заказ не найден).');
             }
        } else {
             error_log("Ошибка обновления статуса заказа #" . $order_id . ": " . $stmt->error);
             setFlashMessage('error', 'Не удалось обновить статус заказа: ' . $stmt->error);
        }
        $stmt->close();
    } else {
         error_log("Ошибка подготовки запроса обновления статуса: " . $conn->error);
         setFlashMessage('error', 'Ошибка сервера при обновлении статуса.');
    }

} else {
    setFlashMessage('error', 'Неверные данные для обновления статуса заказа.');
}

unset($_SESSION['csrf_token']); // Сброс токена

// Перенаправляем обратно на страницу деталей заказа (если она была открыта) или на общий список
$redirect_url = $base_redirect_url;
if ($order_id) {
    $redirect_url .= '?id=' . $order_id;
    // Сохраняем фильтр, если он был
    if (isset($_SESSION['_previous_order_filter'])) { // Простой способ запомнить фильтр
         $redirect_url .= '&status=' . urlencode($_SESSION['_previous_order_filter']);
         unset($_SESSION['_previous_order_filter']);
    } elseif (isset($_GET['status'])) { // Если фильтр был в GET при отправке формы
        $redirect_url .= '&status=' . urlencode($_GET['status']);
    }
}

// Запоминаем фильтр перед редиректом (немного костыльно, но для студенческой работы сойдет)
if (isset($_GET['status'])) {
    $_SESSION['_previous_order_filter'] = $_GET['status'];
}


header('Location: ' . $redirect_url);
exit;
?>