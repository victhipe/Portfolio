<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

checkUserRole('admin');

// Проверка CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF).');
     header('Location: ' . BASE_URL . 'admin/manage_categories.php');
     exit;
}

$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;

if ($category_id) {
     $sql = "DELETE FROM categories WHERE id = ?";
     $stmt = $conn->prepare($sql);
     if ($stmt) {
          $stmt->bind_param("i", $category_id);
          if ($stmt->execute()) {
               if ($stmt->affected_rows > 0) {
                    setFlashMessage('success', 'Категория успешно удалена.');
               } else {
                   setFlashMessage('error', 'Категория с указанным ID не найдена.');
               }
          } else {
               error_log("Ошибка удаления категории ID {$category_id}: " . $stmt->error);
               // Проверка на FK constraint violation (products.category_id)
               if ($conn->errno == 1451) {
                   setFlashMessage('error', 'Не удалось удалить категорию, так как в ней есть товары. Сначала удалите или переместите товары.');
               } else {
                   setFlashMessage('error', 'Не удалось удалить категорию: ' . $stmt->error);
               }
          }
          $stmt->close();
     } else {
          error_log("Ошибка подготовки удаления категории: " . $conn->error);
          setFlashMessage('error', 'Ошибка сервера при удалении категории.');
     }

} else {
    setFlashMessage('error', 'Неверный ID категории для удаления.');
}

unset($_SESSION['csrf_token']);
header('Location: ' . BASE_URL . 'admin/manage_categories.php');
exit;
?>