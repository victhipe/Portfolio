<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

checkUserRole('admin'); // Только админ

// Проверка CSRF токена
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
     setFlashMessage('error', 'Ошибка безопасности (CSRF). Попробуйте снова.');
     // Редирект на страницу управления МЕНЮ
     header('Location: ' . BASE_URL . 'admin/manage_menu.php');
     exit;
}

// --- Получение данных из формы ---
// ID элемента теперь item_id
$item_id = isset($_POST['item_id']) && !empty($_POST['item_id']) ? (int)$_POST['item_id'] : null;
$is_editing = ($item_id !== null);

$category = trim($_POST['category'] ?? '');
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = isset($_POST['price']) ? filter_var($_POST['price'], FILTER_VALIDATE_FLOAT) : false;
$is_available = isset($_POST['is_available']) ? 1 : 0;
$delete_image = isset($_POST['delete_image']) ? 1 : 0;

// Допустимые категории (должны совпадать с ENUM в БД и формой)
$allowed_categories = ['pizza', 'drink', 'salad', 'snack', 'dessert'];

// --- Валидация ---
$errors = [];
if (empty($category) || !in_array($category, $allowed_categories)) {
     $errors[] = "Не выбрана или неверная категория.";
}
if (empty($name)) {
    $errors[] = "Название пункта меню обязательно.";
}
if ($price === false || $price < 0) {
    $errors[] = "Цена указана некорректно.";
}

// --- Обработка изображения ---
$image_path = null; // Путь для записи в БД (относительно /images/)
$base_upload_dir = __DIR__ . '/../images/'; // Базовая папка для загрузки
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_file_size = 2 * 1024 * 1024; // 2 MB

// Получаем старый путь изображения, если редактируем
$old_image_path = null;
if ($is_editing) {
    // Используем новую таблицу menu_items
    $stmt_old = $conn->prepare("SELECT image_path FROM menu_items WHERE id = ?");
    if($stmt_old) {
        $stmt_old->bind_param("i", $item_id);
        $stmt_old->execute();
        $result_old = $stmt_old->get_result();
        if ($row_old = $result_old->fetch_assoc()) {
            $old_image_path = $row_old['image_path']; // Путь вида 'pizza/margarita.jpg' или 'drinks/cola.jpg'
        }
        $stmt_old->close();
    } else {
        // Ошибка получения старого пути - не критично, но стоит залогировать
        error_log("Не удалось получить старый путь изображения для ID: " . $item_id);
    }
}


// Если загружается новый файл
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];

    // Проверка типа и размера (как было)
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) { $errors[] = "Недопустимый тип файла..."; }
    if ($file['size'] > $max_file_size) { $errors[] = "Файл слишком большой..."; }

    // Продолжаем только если нет ошибок валидации файла и категории
    if (empty($errors)) {
        // Определяем папку назначения на основе категории
        $target_subdir = $category . '/'; // e.g., 'pizza/'
        $target_dir_full_path = $base_upload_dir . $target_subdir;

        // Создаем директорию категории, если ее нет
        if (!is_dir($target_dir_full_path)) {
            if (!mkdir($target_dir_full_path, 0775, true)) {
                $errors[] = "Не удалось создать директорию для изображений категории: " . escape($target_dir_full_path);
                // Дальнейшая загрузка файла невозможна
            }
        }

        // Генерируем уникальное имя файла (если директория создана или уже была)
        if (empty($errors)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            if (empty($safe_name)) $safe_name = $category . '_image';
            $new_filename = time() . '_' . $safe_name . '.' . strtolower($extension);
            $destination = $target_dir_full_path . $new_filename;

            // Перемещаем файл
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Путь для БД: 'category/filename.ext'
                $image_path = $target_subdir . $new_filename;

                // Удаляем старый файл, если он был и успешно загружен новый
                // Используем $base_upload_dir и $old_image_path (который уже содержит подпапку)
                if ($is_editing && $old_image_path && file_exists($base_upload_dir . $old_image_path)) {
                     unlink($base_upload_dir . $old_image_path);
                }
            } else {
                $errors[] = "Не удалось переместить загруженный файл изображения.";
            }
        }
    }
} elseif ($is_editing) {
     // Если новый файл не загружался, проверяем, нужно ли удалить старый
     if ($delete_image && $old_image_path) {
          // Используем $base_upload_dir и $old_image_path
          if (file_exists($base_upload_dir . $old_image_path)) {
               unlink($base_upload_dir . $old_image_path);
          }
          $image_path = null; // Очищаем путь в БД
     } else {
          // Иначе оставляем старый путь (если не удаляем и не загружаем новый)
          $image_path = $old_image_path;
     }
}
// Если добавляется новый элемент без картинки, $image_path останется null

// --- Если есть ошибки валидации или загрузки файла ---
if (!empty($errors)) {
    $_SESSION['form_data'] = $_POST; // Сохраняем данные формы
    setFlashMessage('error', implode('<br>', $errors));
    // Перенаправляем обратно на форму добавления/редактирования МЕНЮ
    $redirect_url = $is_editing
        ? BASE_URL . 'admin/add_edit_menu_item.php?id=' . $item_id
        : BASE_URL . 'admin/add_edit_menu_item.php';
    header('Location: ' . $redirect_url);
    exit;
}

// --- Ошибок нет, сохраняем данные в БД ---

if ($is_editing) {
    // Обновление существующего пункта меню
    // Используем таблицу menu_items и добавляем category
    $sql = "UPDATE menu_items SET category = ?, name = ?, description = ?, price = ?, image_path = ?, is_available = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Ошибка подготовки обновления пункта меню: " . $conn->error);
        setFlashMessage('error', 'Ошибка сервера при обновлении пункта меню.');
    } else {
        // Типы: s (category), s (name), s (description), d (price), s (image_path), i (is_available), i (id)
        $stmt->bind_param("sssdsii", $category, $name, $description, $price, $image_path, $is_available, $item_id);
        if ($stmt->execute()) {
             setFlashMessage('success', 'Пункт меню успешно обновлен.');
        } else {
             error_log("Ошибка выполнения обновления пункта меню ID {$item_id}: " . $stmt->error);
             setFlashMessage('error', 'Не удалось обновить пункт меню: ' . $stmt->error);
        }
        $stmt->close();
    }
} else {
    // Добавление нового пункта меню
    // Используем таблицу menu_items и добавляем category
    $sql = "INSERT INTO menu_items (category, name, description, price, image_path, is_available) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Ошибка подготовки добавления пункта меню: " . $conn->error);
        setFlashMessage('error', 'Ошибка сервера при добавлении пункта меню.');
     } else {
         // Типы: s (category), s (name), s (description), d (price), s (image_path), i (is_available)
         $stmt->bind_param("sssdsi", $category, $name, $description, $price, $image_path, $is_available);
        if ($stmt->execute()) {
             setFlashMessage('success', 'Пункт меню успешно добавлен.');
        } else {
             error_log("Ошибка выполнения добавления пункта меню: " . $stmt->error);
             setFlashMessage('error', 'Не удалось добавить пункт меню: ' . $stmt->error);
             // Если произошла ошибка при вставке, и был загружен файл, его стоит удалить
             if ($image_path && file_exists($base_upload_dir . $image_path)) {
                unlink($base_upload_dir . $image_path);
             }
        }
         $stmt->close();
     }
}

unset($_SESSION['csrf_token']); // Сброс токена
unset($_SESSION['form_data']); // Очистка данных формы

// Перенаправляем на страницу управления МЕНЮ
header('Location: ' . BASE_URL . 'admin/manage_menu.php');
exit;

?>