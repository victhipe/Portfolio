<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $fullname = $_POST['fullname'];
    $gender = $_POST['gender'];

    if ($password !== $confirm_password) {
        echo "Пароли не совпадают.";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $avatar_path = 'avatars/avatar.png';
    $conn = new mysqli('localhost', 'root', '', 'Forum_Dex');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, gender, avatar_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hashed_password, $fullname, $gender, $avatar_path);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Ошибка: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Недопустимый метод запроса.";
}
?>