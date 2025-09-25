<?php
session_start();

if (isset($_SESSION['username'])) {
    $servername = "localhost";
    $dbname = "Forum_DEX";
    $dbusername = "root";
    $dbpassword = "";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (isset($_POST['post_id'])) {
            $post_id = $_POST['post_id'];
            $username = $_SESSION['username'];

            // Проверяем, является ли пользователь автором поста или администратором
            $stmt = $conn->prepare("SELECT * FROM posts WHERE id = :post_id AND username = :username");
            $stmt->execute(['post_id' => $post_id, 'username' => $username]);
            $post = $stmt->fetch();

            if ($post) {
                // Удаление всех комментариев к посту, включая ответы
                $stmt = $conn->prepare("DELETE FROM comments WHERE post_id = :post_id");
                $stmt->execute(['post_id' => $post_id]);

                // Удаление самого поста
                $stmt = $conn->prepare("DELETE FROM posts WHERE id = :post_id");
                $stmt->execute(['post_id' => $post_id]);

                echo "Пост и все его комментарии были успешно удалены.";
            } else {
                echo "У вас нет прав на удаление этого поста.";
            }
        } else {
            echo "Не указан ID поста.";
        }
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
    if ($stmt->execute()) {
        header("Location: index.php");
    } else {
        echo "Ошибка при удалении поста.";
    }
    exit();
    $conn = null;
} else {
    echo "Вы не авторизованы.";
}
?>
