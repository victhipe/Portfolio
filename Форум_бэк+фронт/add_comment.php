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

        if (isset($_POST['content']) && isset($_POST['post_id'])) {
            $post_id = $_POST['post_id'];
            $parent_id = isset($_POST['parent_id']) ? $_POST['parent_id'] : null;
            $username = $_SESSION['username'];
            $content = $_POST['content'];

            $stmt = $conn->prepare("INSERT INTO comments (post_id, parent_id, username, content) VALUES (:post_id, :parent_id, :username, :content)");
            $stmt->execute(['post_id' => $post_id, 'parent_id' => $parent_id, 'username' => $username, 'content' => $content]);

            header("Location: post.php?id=" . $post_id);
        } else {
            throw new Exception("Ошибка: Не все данные отправлены.");
        }
    } catch(PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    } catch(Exception $e) {
        echo $e->getMessage();
    }
    if ($stmt->execute()) {
        header("Location: index.php#post-" . $post_id);
    } else {
        echo "Ошибка при добавлении комментария.";
    }
    exit();

    $conn = null;
} else {
    echo "Вы не авторизованы.";
}
?>
