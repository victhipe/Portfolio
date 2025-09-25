<?php
// Настройки подключения к базе данных
define('DB_HOST', 'localhost'); // Или ваш хост БД
define('DB_USER', 'root');      // Ваше имя пользователя БД
define('DB_PASS', '');          // Ваш пароль БД
define('DB_NAME', 'tasty_pizza_db'); // Имя вашей базы данных

// Другие настройки
define('SITE_NAME', 'Вкусная пицца!');
define('BASE_URL', 'http://localhost/вкусная_пицца/'); // Замените на ваш URL

// Настройки сессий
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1); // Повышает безопасность
ini_set('session.cookie_samesite', 'Lax'); // или 'Strict'

// Запуск сессии в начале каждого запроса, где она нужна
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>