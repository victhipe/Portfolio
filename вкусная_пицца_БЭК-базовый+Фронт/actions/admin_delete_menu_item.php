<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

checkUserRole('admin');

// Проверка CSRF токена
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     header('Location: ' . BASE_URL . 'admin/manage_menu.php'); // Изменено на manage_menu.php
     exit;
}

$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : null; // Изменено на item_id

if ($item_id) {
     $sql = "DELETE FROM menu_items WHERE id = ?"; // Изменено на menu_items
     $stmt = $conn->prepare($sql);
     if ($stmt) {
          $stmt->bind_param("i", $item_id); // Используем item_id
          if ($stmt->execute()) {
               if ($stmt->affected_rows > 0) {
                    setFlashMessage('success', 'Пункт меню успешно удален.'); // Изменено сообщение
               } else {
                   setFlashMessage('error', 'Пункт меню с указанным ID не найден.'); // Изменено сообщение
               }
          } else {
               error_log("Ошибка удаления пункта меню ID {$item_id}: " . $stmt->error); // Изменено сообщение и item_id
               setFlashMessage('error', 'Не удалось удалить пункт меню: ' . $stmt->error); // Изменено сообщение
          }
          $stmt->close();
     } else {
          error_log("Ошибка подготовки удаления пункта меню: " . $conn->error); // Изменено сообщение
          setFlashMessage('error', 'Ошибка сервера при удалении пункта меню.'); // Изменено сообщение
     }

} else {
    setFlashMessage('error', 'Неверный ID пункта меню для удаления.'); // Изменено сообщение
}

unset($_SESSION['csrf_token']);
header('Location: ' . BASE_URL . 'admin/manage_menu.php'); // Изменено на manage_menu.php
exit;
?>