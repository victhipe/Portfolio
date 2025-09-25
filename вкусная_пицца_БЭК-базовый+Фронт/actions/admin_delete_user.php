<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

checkUserRole('admin');

// Проверка CSRF токена
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . BASE_URL . 'admin/manage_users.php');
     exit;
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;

// Защита от самоликвидации
if ($user_id === getCurrentUserId()) {
    setFlashMessage('error', 'Вы не можете удалить свой собственный аккаунт.');
    header('Location: ' . BASE_URL . 'admin/manage_users.php');
    exit;
}

if ($user_id) {
     // ВАЖНО: Подумать о связанных данных!
     // Что делать с заказами удаленного пользователя?
     // В схеме БД сейчас стоит `ON DELETE SET NULL` для `orders.user_id`.
     // Это означает, что заказы останутся, но будут "отвязаны" от пользователя.
     // Если нужно удалять и заказы (что обычно не делают), нужно изменить FK на ON DELETE CASCADE.

     // Можно добавить доп. проверку, если пользователь - админ (оставить хотя бы одного админа?)

     $sql = "DELETE FROM users WHERE id = ?";
     $stmt = $conn->prepare($sql);
     if ($stmt) {
          $stmt->bind_param("i", $user_id);
          if ($stmt->execute()) {
               if ($stmt->affected_rows > 0) {
                    setFlashMessage('success', 'Пользователь успешно удален.');
               } else {
                   setFlashMessage('error', 'Пользователь с указанным ID не найден.');
               }
          } else {
               error_log("Ошибка удаления пользователя ID {$user_id}: " . $stmt->error);
               // Проверка на FK ошибки, если бы они были (например, если бы FK в orders был RESTRICT)
               if ($conn->errno == 1451) {
                   setFlashMessage('error', 'Не удалось удалить пользователя, так как с ним связаны другие данные (например, заказы).');
               } else {
                   setFlashMessage('error', 'Не удалось удалить пользователя: ' . $stmt->error);
               }
          }
          $stmt->close();
     } else {
          error_log("Ошибка подготовки удаления пользователя: " . $conn->error);
          setFlashMessage('error', 'Ошибка сервера при удалении пользователя.');
     }

} else {
    setFlashMessage('error', 'Неверный ID пользователя для удаления.');
}

unset($_SESSION['csrf_token']);
header('Location: ' . BASE_URL . 'admin/manage_users.php');
exit;
?>