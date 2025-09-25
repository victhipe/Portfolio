<?php
// --- НАЧАЛО ФАЙЛА ---
require_once __DIR__ . '/../includes/functions.php';
checkUserRole('admin');

require_once __DIR__ . '/../includes/db_connect.php';

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$is_editing = ($item_id !== null);
$page_title = $is_editing ? "Редактирование пункта меню" : "Добавление пункта меню";

// Доступные категории из ENUM схемы БД
$allowed_categories = ['pizza', 'drink', 'salad', 'snack', 'dessert'];
$category_names_map = [ // Для отображения в select
    'pizza' => 'Пицца',
    'drink' => 'Напиток',
    'salad' => 'Салат',
    'snack' => 'Закуска',
    'dessert' => 'Десерт'
];


$item = [
    'id' => null,
    'category' => 'pizza', // Категория по умолчанию при добавлении
    'name' => '',
    'description' => '',
    'price' => '',
    'image_path' => '',
    'is_available' => true
];

if ($is_editing) {
    // Используем menu_items вместо pizzas
    $sql = "SELECT id, category, name, description, price, image_path, is_available FROM menu_items WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item_data = $result->fetch_assoc();
        $stmt->close();
        if ($item_data) {
            $item = $item_data; // Заполняем данными из БД
        } else {
            setFlashMessage('error', 'Пункт меню не найден.');
            header('Location: ' . BASE_URL . 'admin/manage_menu.php'); // Редирект на новый файл списка
            exit;
        }
    }
}

// Восстановление данных из сессии
if (isset($_SESSION['form_data'])) {
    $item = array_merge($item, $_SESSION['form_data']);
    unset($_SESSION['form_data']);
}

require_once __DIR__ . '/_admin_header.php';
// --- КОНЕЦ ШАПКИ ---
?>

<h1><?php echo escape($page_title); ?></h1>

<?php
// Сообщения об ошибках/успехе
$error_message = getFlashMessage('error');
if ($error_message) echo '<div class="flash-message error">' . $error_message . '</div>';
?>

<!-- Форма теперь отправляет на admin_save_menu_item.php -->
<form action="<?php echo BASE_URL; ?>actions/admin_save_menu_item.php" method="post" enctype="multipart/form-data">
    <!-- ID теперь item_id -->
    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

    <div>
        <label for="item_category">Категория: <span class="required">*</span></label>
        <select id="item_category" name="category" required>
            <option value="">-- Выберите категорию --</option>
            <?php foreach ($allowed_categories as $cat_key): ?>
                <option value="<?php echo $cat_key; ?>" <?php echo ($item['category'] === $cat_key) ? 'selected' : ''; ?>>
                    <?php echo escape($category_names_map[$cat_key] ?? ucfirst($cat_key)); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label for="item_name">Название: <span class="required">*</span></label>
        <input type="text" id="item_name" name="name" value="<?php echo escape($item['name']); ?>" required maxlength="100">
    </div>
    <div>
        <label for="item_description">Описание:</label>
        <textarea id="item_description" name="description" rows="4"><?php echo escape($item['description'] ?? ''); ?></textarea>
    </div>
    <div>
        <!-- Обновленная подпись для цены -->
        <label for="item_price">Цена (руб.) (Базовая для пиццы 42см): <span class="required">*</span></label>
        <input type="number" id="item_price" name="price" value="<?php echo escape($item['price']); ?>" required step="0.01" min="0">
    </div>
    <div>
        <label for="item_image">Изображение:</label>
        <input type="file" id="item_image" name="image" accept="image/jpeg, image/png, image/gif, image/webp">
        <?php if ($is_editing && !empty($item['image_path']) && file_exists(__DIR__ . '/../images/' . $item['image_path'])): ?>
            <p>Текущее изображение:</p>
            <img src="<?php echo BASE_URL . 'images/' . escape($item['image_path']); ?>?v=<?php echo time(); ?>" alt="Текущее изображение" style="max-width: 150px; margin-top: 5px;">
            <br>
            <input type="checkbox" name="delete_image" id="delete_image" value="1">
            <label for="delete_image">Удалить текущее изображение</label>
        <?php elseif ($is_editing && !empty($item['image_path'])): ?>
             <p style="color:red;">Файл текущего изображения не найден: <?php echo escape($item['image_path']); ?></p>
        <?php endif; ?>
         <small>Макс. размер: 2MB. Путь будет сохранен относительно папки /images/.</small>
    </div>
     <div>
        <label for="item_available">Доступно для заказа:</label>
        <input type="checkbox" id="item_available" name="is_available" value="1" <?php echo ($item['is_available'] ?? true) ? 'checked' : ''; ?>>
    </div>

    <div>
        <button type="submit" class="button"><?php echo $is_editing ? 'Сохранить изменения' : 'Добавить пункт меню'; ?></button>
        <!-- Ссылка на новый файл списка -->
        <a href="<?php echo BASE_URL; ?>admin/manage_menu.php" class="button secondary">Отмена</a>
    </div>
</form>
<style> /* Стили можно вынести в admin_style.css */
    .required { color: red; }
    small { display: block; font-size: 0.8em; color: #666; margin-top: -5px; margin-bottom: 10px;}
    .button.secondary { background-color: #ccc; color: #333; }
    .button.secondary:hover { background-color: #bbb; }
</style>

<?php
// --- НАЧАЛО ФУТЕРА ---
require_once __DIR__ . '/_admin_footer.php';
// --- КОНЕЦ ФАЙЛА ---
?>