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

// Получение данных
$category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$name = trim($_POST['name'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$description = trim($_POST['description'] ?? '');
$sort_order = isset($_POST['sort_order']) ? filter_var($_POST['sort_order'], FILTER_VALIDATE_INT) : 0;
if ($sort_order === false) $sort_order = 0; // Если не число, ставим 0

$is_editing = ($category_id !== null);

// Валидация
$errors = [];
if (empty($name)) $errors[] = "Название категории обязательно.";
if (empty($slug)) {
    $errors[] = "Slug обязателен.";
} elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    $errors[] = "Slug содержит недопустимые символы.";
} else {
    // Проверка уникальности slug
    $sql_check_slug = "SELECT id FROM categories WHERE slug = ?" . ($is_editing ? " AND id != ?" : "");
    $stmt_check_slug = $conn->prepare($sql_check_slug);
    if ($is_editing) {
        $stmt_check_slug->bind_param("si", $slug, $category_id);
    } else {
        $stmt_check_slug->bind_param("s", $slug);
    }
    $stmt_check_slug->execute();
    if ($stmt_check_slug->get_result()->num_rows > 0) {
        $errors[] = "Такой Slug уже используется другой категорией.";
    }
    $stmt_check_slug->close();
}

// Если есть ошибки
if (!empty($errors)) {
    $_SESSION['form_data'] = $_POST;
    setFlashMessage('error', implode('<br>', $errors));
    $redirect_url = $is_editing ? BASE_URL . 'admin/add_edit_category.php?id=' . $category_id : BASE_URL . 'admin/add_edit_category.php';
    header('Location: ' . $redirect_url);
    exit;
}

// Сохранение в БД
if ($is_editing) {
    // Обновление
    $sql = "UPDATE categories SET name = ?, slug = ?, description = ?, sort_order = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssii", $name, $slug, $description, $sort_order, $category_id);
        if ($stmt->execute()) {
             setFlashMessage('success', 'Категория успешно обновлена.');
        } else {
             error_log("Ошибка обновления категории ID {$category_id}: " . $stmt->error);
             setFlashMessage('error', 'Не удалось обновить категорию: ' . $stmt->error);
        }
        $stmt->close();
    } else {
         error_log("Ошибка подготовки обновления категории: " . $conn->error);
         setFlashMessage('error', 'Ошибка сервера при обновлении категории.');
    }
} else {
    // Добавление
    $sql = "INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
     if ($stmt) {
        $stmt->bind_param("sssi", $name, $slug, $description, $sort_order);
        if ($stmt->execute()) {
             setFlashMessage('success', 'Категория успешно добавлена.');
        } else {
             error_log("Ошибка добавления категории: " . $stmt->error);
             // Проверка на дубликат UNIQUE ключа (name или slug)
             if ($conn->errno == 1062) { // MySQL error code for duplicate entry
                setFlashMessage('error', 'Категория с таким названием или Slug уже существует.');
             } else {
                setFlashMessage('error', 'Не удалось добавить категорию: ' . $stmt->error);
             }
        }
        $stmt->close();
     } else {
        error_log("Ошибка подготовки добавления категории: " . $conn->error);
        setFlashMessage('error', 'Ошибка сервера при добавлении категории.');
     }
}

unset($_SESSION['csrf_token']);
unset($_SESSION['form_data']);
header('Location: ' . BASE_URL . 'admin/manage_categories.php');
exit;
?>