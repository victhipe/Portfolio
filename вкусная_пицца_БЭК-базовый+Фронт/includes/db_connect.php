<?php
require_once __DIR__ . '/../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Проверка соединения
if ($conn->connect_error) {
    // В реальном приложении здесь должна быть более красивая страница ошибки
    die("Ошибка подключения к базе данных: " . $conn->connect_error);
}

// Установка кодировки соединения
if (!$conn->set_charset("utf8mb4")) {
    // Обработка ошибки установки кодировки
    error_log("Ошибка загрузки кодировки символов utf8mb4: " . $conn->error);
    
}

function safeQuery($sql, $params = [], $types = "") {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // Логирование ошибки SQL
        error_log("Ошибка подготовки запроса: " . $conn->error . " | SQL: " . $sql);
        return false; // Возвращаем false при ошибке
    }
    if ($params && $types) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        error_log("Ошибка выполнения запроса: " . $stmt->error . " | SQL: " . $sql);
        $stmt->close();
        return false; // Возвращаем false при ошибке
    }
    $result = $stmt->get_result();
    $stmt->close();
    return $result; // Возвращаем объект mysqli_result или true/false для DML
}

?>