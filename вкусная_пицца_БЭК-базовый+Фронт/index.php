<?php
$page_title = "Меню";
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/header.php';

// Определяем коэффициенты цен для размеров пиццы
define('PRICE_FACTOR_SMALL', 0.7); // 35см = -30% от базовой (42см)
define('PRICE_FACTOR_MEDIUM', 1.0); // 42см = базовая цена
define('PRICE_FACTOR_LARGE', 1.3); // 55см = +30% от базовой (42см)

// Получаем все доступные пункты меню, сортируем по категории и имени
$sql = "SELECT id, category, name, description, price, image_path, is_available
        FROM menu_items
        WHERE is_available = TRUE
        ORDER BY category, name ASC";
$result = safeQuery($sql);

$menu = [];
if ($result && $result->num_rows > 0) {
    while ($item = $result->fetch_assoc()) {
        $menu[$item['category']][] = $item; // Группируем по категории
    }
    $result->free();
}

// Названия категорий для вывода
$category_names = [
    'pizza' => 'Пицца',
    'drink' => 'Напитки',
    'salad' => 'Салаты',
    'snack' => 'Закуски',
    'dessert' => 'Десерты'
];
?>

<h1><?php echo escape($page_title); ?> нашей пиццерии!</h1>
<p>Выберите самые вкусные блюда и напитки.</p>

<?php if (!empty($menu)): ?>
    <?php foreach ($category_names as $category_key => $category_name): ?>
        <?php if (isset($menu[$category_key])): ?>
            <section class="menu-category">
                <h2><?php echo escape($category_name); ?></h2>
                <ul class="menu-list <?php echo $category_key === 'pizza' ? 'pizza-list' : 'other-list'; // Разные стили если нужно ?>">

                    <?php foreach ($menu[$category_key] as $item): ?>
                        <li class="menu-item <?php echo !$item['is_available'] ? 'unavailable' : ''; ?>">
                            <?php
                                $image_url = BASE_URL . 'images/' . (!empty($item['image_path']) && file_exists(__DIR__ . '/images/' . $item['image_path']) ? escape($item['image_path']) : 'placeholder.png');
                                $base_price = (float)$item['price']; // Базовая цена из БД
                            ?>
                            <img src="<?php echo $image_url; ?>" alt="<?php echo escape($item['name']); ?>">
                            <h3><?php echo escape($item['name']); ?></h3>
                            <p class="description"><?php echo escape($item['description'] ?? 'Описание отсутствует.'); ?></p>

                            <form action="<?php echo BASE_URL; ?>actions/add_to_cart.php" method="post" class="add-to-cart-form">
                                <input type="hidden" name="menu_item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                                <?php if ($item['category'] === 'pizza'): ?>
                                    <div class="pizza-size-selector">
                                        <label>Размер:</label>
                                        <select name="size" class="pizza-size-select" data-base-price="<?php echo $base_price; ?>" required>
                                            <option value="35" data-price-factor="<?php echo PRICE_FACTOR_SMALL; ?>">35 см</option>
                                            <option value="42" data-price-factor="<?php echo PRICE_FACTOR_MEDIUM; ?>" selected>42 см</option>
                                            <option value="55" data-price-factor="<?php echo PRICE_FACTOR_LARGE; ?>">55 см</option>
                                        </select>
                                    </div>
                                    <p class="item-price" data-item-id="<?php echo $item['id']; ?>">
                                        Цена: <span class="dynamic-price"><?php echo number_format($base_price * PRICE_FACTOR_MEDIUM, 2, ',', ' '); ?></span> руб.
                                    </p>
                                <?php else: ?>
                                    <p class="item-price">
                                        Цена: <?php echo number_format($base_price, 2, ',', ' '); ?> руб.
                                    </p>
                                <?php endif; ?>

                                <div class="quantity-control">
                                    <label for="qty_<?php echo $item['id']; ?>">Кол-во:</label>
                                    <input type="number" id="qty_<?php echo $item['id']; ?>" name="quantity" value="1" min="1" max="2" style="width: 60px; text-align: center;">
                                </div>

                                <?php if ($item['is_available']): ?>
                                    <button type="submit" class="button add-button">В корзину</button>
                                <?php else: ?>
                                    <p><strong>Временно недоступно</strong></p>
                                    <button type="submit" class="button" disabled>В корзину</button>
                                <?php endif; ?>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <hr class="category-divider">
        <?php endif; ?>
    <?php endforeach; ?>
<?php else: ?>
    <p>К сожалению, в меню пока ничего нет. Загляните позже!</p>
<?php endif; ?>

<style>
/* Стили для меню */
.menu-category { margin-bottom: 30px; }
.menu-category h2 { border-bottom: 2px solid #e8491d; padding-bottom: 5px; margin-bottom: 20px; }
.menu-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); /* Адаптивная сетка */
    gap: 20px;
    list-style: none;
    padding: 0;
}
.menu-item {
    background-color: #fff;
    border: 1px solid #ddd;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}
.menu-item img {
    object-fit: cover;
    margin-bottom: 10px;
    background-color: #eee;
}
.menu-item h3 { margin-top: 0; color: #e8491d; font-size: 1.1em; }
.menu-item .description { font-size: 0.9em; color: #555; min-height: 40px; flex-grow: 1; margin-bottom: 10px; } /* Занимает место */
.item-price { font-weight: bold; font-size: 1.2em; margin: 10px 0; }
.add-to-cart-form { margin-top: auto; } /* Прижимает форму вниз */
.pizza-size-selector { margin-bottom: 10px; }
.pizza-size-selector label { margin-right: 5px; }
.pizza-size-selector select { padding: 5px; }
.quantity-control { margin-bottom: 15px; }
.quantity-control input { padding: 5px; }
.menu-item.unavailable { opacity: 0.6; background-color: #f5f5f5; }
.category-divider { border: 0; height: 1px; background: #ddd; margin: 30px 0; }
</style>

<?php
// Подключаем JS для динамического обновления цен пиццы
$extra_js = ['script.js']; // Убедимся что script.js подключен
require_once __DIR__ . '/includes/footer.php';
?>