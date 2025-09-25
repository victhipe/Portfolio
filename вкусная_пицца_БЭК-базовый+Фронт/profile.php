<?php
$page_title = "Мой профиль";
require_once __DIR__ . '/includes/db_connect.php'; // Подключаем БД и config
require_once __DIR__ . '/includes/functions.php'; // Подключаем функции и сессии

// 1. Проверка авторизации пользователя
if (!isLoggedIn()) {
    setFlashMessage('info', 'Пожалуйста, войдите, чтобы просмотреть ваш профиль.');
    // Сохраняем URL, чтобы вернуться сюда после входа
    $_SESSION['redirect_after_login'] = BASE_URL . 'profile.php';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// 2. Получение ID текущего пользователя
$user_id = getCurrentUserId();

// 3. Получение данных пользователя из базы данных
$user_data = null;
$sql_user = "SELECT username, email, full_name, phone_number, address, created_at FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);

if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    if ($stmt_user->execute()) {
        $result_user = $stmt_user->get_result();
        $user_data = $result_user->fetch_assoc();
    } else {
        error_log("Ошибка выполнения запроса данных пользователя: " . $stmt_user->error);
        // Установить сообщение об ошибке, но не прерывать, чтобы показать историю заказов
        setFlashMessage('error', 'Не удалось выполнить запрос данных профиля.');
    }
    $stmt_user->close();
} else {
    error_log("Ошибка подготовки запроса данных пользователя: " . $conn->error);
    setFlashMessage('error', 'Ошибка сервера при получении данных профиля.');
}


// 4. Получение истории заказов пользователя из базы данных
$orders = [];
$sql_orders = "SELECT id, order_time, total_price, status FROM orders WHERE user_id = ? ORDER BY order_time DESC";
$stmt_orders = $conn->prepare($sql_orders);

if ($stmt_orders) {
    $stmt_orders->bind_param("i", $user_id);
    if ($stmt_orders->execute()) {
        $result_orders = $stmt_orders->get_result();
        while ($row = $result_orders->fetch_assoc()) {
            $orders[] = $row;
        }
    } else {
        error_log("Ошибка выполнения запроса истории заказов: " . $stmt_orders->error);
        setFlashMessage('error', 'Не удалось выполнить запрос истории заказов.');
    }
    $stmt_orders->close();
} else {
    error_log("Ошибка подготовки запроса истории заказов: " . $conn->error);
    setFlashMessage('error', 'Ошибка сервера при получении истории заказов.');
}


// 5. Подключение шапки сайта
require_once __DIR__ . '/includes/header.php';
?>

<h1><?php echo escape($page_title); ?></h1>

<?php
// Отображение flash-сообщений (если они были установлены при получении данных)
$error_msg = getFlashMessage('error');
if ($error_msg) echo '<div class="flash-message error">' . escape($error_msg) . '</div>';
$info_msg = getFlashMessage('info');
if ($info_msg) echo '<div class="flash-message info">' . escape($info_msg) . '</div>';
$success_msg = getFlashMessage('success');
if ($success_msg) echo '<div class="flash-message success">' . escape($success_msg) . '</div>';
?>


<?php if ($user_data): ?>
    <div class="profile-info card"> <!-- Обернем в карточку для стиля -->
        <h2>Ваши данные</h2>
        <ul>
            <li><strong>Имя пользователя:</strong> <?php echo escape($user_data['username']); ?></li>
            <li><strong>Email:</strong> <?php echo escape($user_data['email']); ?></li>
            <li><strong>Полное имя:</strong> <?php echo escape($user_data['full_name'] ?: 'Не указано'); ?></li>
            <li><strong>Телефон:</strong> <?php echo escape($user_data['phone_number'] ?: 'Не указан'); ?></li>
            <li><strong>Адрес по умолчанию:</strong> <?php echo nl2br(escape($user_data['address'] ?: 'Не указан')); ?></li>
            <li><strong>Дата регистрации:</strong> <?php echo date('d.m.Y', strtotime($user_data['created_at'])); ?></li>
        </ul>
        <p><a href="<?php echo BASE_URL; ?>profile_edit.php" class="button">Редактировать профиль</a></p>
    </div>
<?php elseif(!$error_msg): // Если данных нет, но и явной ошибки не было ?>
    <p class="error">Не удалось загрузить данные вашего профиля.</p>
<?php endif; ?>

<hr style="margin: 30px 0;">

<h2>История ваших заказов</h2>

<?php if (!empty($orders)): ?>
    <table class="admin-table profile-orders-table"> <!-- Используем стиль админ-таблицы -->
        <thead>
            <tr>
                <th>ID Заказа</th>
                <th>Дата и время</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Детали</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo $order['id']; ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($order['order_time'])); ?></td>
                    <td><?php echo number_format($order['total_price'], 2, ',', ' '); ?> руб.</td>
                    <td>
                        <span class="status status-<?php echo escape($order['status']); ?>">
                            <?php echo escape(ucfirst($order['status'])); // Делаем первую букву заглавной ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>view_order.php?id=<?php echo $order['id']; ?>" class="button small view">Смотреть</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif(!$error_msg): // Если заказов нет, но и ошибки не было ?>
    <p>У вас пока нет оформленных заказов. <a href="<?php echo BASE_URL; ?>">Пора выбрать пиццу!</a></p>
<?php endif; ?>

<?php
// Добавим немного стилей прямо здесь для наглядности (лучше в CSS файл)
?>
<style>
    .profile-info.card {
        background-color: #fff;
        border: 1px solid #ddd;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .profile-info ul {
        list-style: none;
        padding-left: 0;
    }
     .profile-info li {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
     .profile-info li:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .profile-orders-table {
         margin-top: 15px;
    }
    /* Стили для статусов (пример) */
    .status {
        padding: 3px 8px;
        border-radius: 12px; /* Скругленные края */
        font-size: 0.85em;
        color: #fff;
        background-color: #888; /* По умолчанию серый */
        white-space: nowrap; /* Чтобы текст не переносился */
    }
    .status-new { background-color: #007bff; } /* Синий */
    .status-processing { background-color: #ffc107; color:#333; } /* Желтый */
    .status-delivering { background-color: #17a2b8; } /* Бирюзовый */
    .status-completed { background-color: #28a745; } /* Зеленый */
    .status-cancelled { background-color: #dc3545; } /* Красный */

    .button.small {
        padding: 4px 10px;
        font-size: 0.9em;
    }
    .button.view {
        background-color: #5bc0de; /* Светло-синий */
    }
     .button.view:hover {
        background-color: #31b0d5;
    }

</style>


<?php
// Подключение подвала сайта
require_once __DIR__ . '/includes/footer.php';
?>