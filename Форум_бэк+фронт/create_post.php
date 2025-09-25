<?php
session_start();

if (isset($_SESSION['username']) && isset($_POST['title']) && isset($_POST['content']) && isset($_POST['section'])) {
    $username = $_SESSION['username'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $section = $_POST['section'];

    if (strlen($content) > 1024) {
        echo "Контент превышает допустимое количество символов. <a href='index.php'>Вернуться на главную</a>";
        exit;
    }

    $servername = "localhost";
    $dbname = "Forum_DEX";
    $dbusername = "root";
    $dbpassword = "";

    $imagePath = '';
    $audioPath = '';
    $videoPath = '';

    // Обработка загрузки изображения
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $imageName = basename($_FILES['image']['name']);
        $imagePath = $uploadDir . $imageName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            echo "Ошибка при загрузке изображения. <a href='index.php'>Вернуться на главную</a>";
            exit;
        }
    }

    // Обработка загрузки аудио
    if (isset($_FILES['audio']) && $_FILES['audio']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'music/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $audioName = basename($_FILES['audio']['name']);
        $audioPath = $uploadDir . $audioName;

        if (!move_uploaded_file($_FILES['audio']['tmp_name'], $audioPath)) {
            echo "Ошибка при загрузке аудио. <a href='index.php'>Вернуться на главную</a>";
            exit;
        }
    }

    // Обработка загрузки видео
    if (isset($_FILES['video']) && $_FILES['video']['error'] == UPLOAD_ERR_OK) {
        if ($_FILES['video']['size'] > 104857600) { // 100MB в байтах
            echo "Видео превышает допустимый размер в 100 мегабайт. <a href='index.php'>Вернуться на главную</a>";
            exit;
        }

        $uploadDir = 'video/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $videoName = basename($_FILES['video']['name']);
        $videoPath = $uploadDir . $videoName;

        if (!move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)) {
            echo "Ошибка при загрузке видео. <a href='index.php'>Вернуться на главную</a>";
            exit;
        }
    }

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("INSERT INTO posts (username, title, content, image_path, audio_path, video_path, section) VALUES (:username, :title, :content, :image_path, :audio_path, :video_path, :section)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':image_path', $imagePath);
        $stmt->bindParam(':audio_path', $audioPath);
        $stmt->bindParam(':video_path', $videoPath);
        $stmt->bindParam(':section', $section);
        $stmt->execute();

        echo "Пост успешно создан. <a href='index.php'>Вернуться на главную</a>";
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
    if ($stmt->execute()) {
        header("Location: index.php");
    } else {
        echo "Ошибка при создании поста.";
    }
    exit();

    $conn = null;
} else {
    echo "Пожалуйста, заполните все поля. <a href='index.php'>Вернуться на главную</a>";
}
?>