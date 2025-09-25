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

        if (isset($_POST['comment_id'])) {
            $comment_id = $_POST['comment_id'];
            $username = $_SESSION['username'];

            // Проверяем, является ли пользователь автором комментария или администратором
            $stmt = $conn->prepare("SELECT * FROM comments WHERE id = :comment_id AND username = :username");
            $stmt->execute(['comment_id' => $comment_id, 'username' => $username]);
            $comment = $stmt->fetch();

            if ($comment) {
                // Удаление всех ответов на комментарий
                $stmt = $conn->prepare("DELETE FROM comments WHERE parent_id = :comment_id");
                $stmt->execute(['comment_id' => $comment_id]);

                // Удаление самого комментария
                $stmt = $conn->prepare("DELETE FROM comments WHERE id = :comment_id");
                $stmt->execute(['comment_id' => $comment_id]);

                echo "Комментарий и все его ответы были успешно удалены.";
            } else {
                echo "У вас нет прав на удаление этого комментария.";
            }
        } else {
            echo "Не указан ID комментария.";
        }
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
    if ($stmt->execute()) {
        header("Location: index.php");
    } else {
        echo "Ошибка при удалении комментария.";
    }
    exit();

    $conn = null;
} else {
    echo "Вы не авторизованы.";
}
?>
