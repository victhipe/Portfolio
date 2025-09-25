<?php
session_start();

$servername = "localhost";
$dbname = "Forum_DEX";
$dbusername = "root";
$dbpassword = "";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['username'])) {
        echo "Ошибка: имя пользователя не указано.";
        exit();
    }

    $username = $_GET['username'];

    // Fetch user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "Пользователь не найден.";
        exit();
    }

    // Fetch user posts
    $stmt = $conn->prepare("SELECT * FROM posts WHERE username = :username ORDER BY created_at DESC");
    $stmt->execute(['username' => $username]);
    $posts = $stmt->fetchAll();

    // Fetch user comments
    $stmt = $conn->prepare("SELECT * FROM comments WHERE username = :username ORDER BY created_at DESC");
    $stmt->execute(['username' => $username]);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
    exit();
}

function display_post($post) {
    echo "<div class='post'>";
    echo "<h3>" . htmlspecialchars($post['title']) . "</h3>";
    echo "<p>" . nl2br(htmlspecialchars($post['content'])) . "</p>";
    if (!empty($post['image_path'])) {
        echo "<img src='" . htmlspecialchars($post['image_path']) . "' alt='Изображение к посту' style='max-width: 200px;'><br>";
    }
    if (!empty($post['audio_path'])) {
        echo "<audio controls>
                <source src='" . htmlspecialchars($post['audio_path']) . "' type='audio/mpeg'>
                Ваш браузер не поддерживает элемент audio.
              </audio><br>";
    }
    if (!empty($post['video_path'])) {
        echo "<video controls style='max-width: 400px;'>
                <source src='" . htmlspecialchars($post['video_path']) . "' type='video/mp4'>
                Ваш браузер не поддерживает элемент video.
              </video><br>";
    }
    echo "</div>";
}

function display_comment($comment) {
    echo "<div class='comment'>";
    echo "<p>" . nl2br(htmlspecialchars($comment['content'])) . "</p>";
    echo "</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <title>Профиль пользователя</title>
    <style>
        body {
            margin: 0;
            background-color: #5b83c1;
            font-family: 'Istok Web', sans-serif;
            color: white;
        }
        .container {
            padding: 20px;
            max-width: 800px;
            margin: auto;
        }
        .profile-header {
            display: flex;
            align-items: center;
        }
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-right: 20px;
        }
        .update-avatar-form {
            margin-top: 20px;
        }
        .post, .comment {
            background-color: #6996FF;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <?php if (!empty($user['avatar_path'])): ?>
                <img src="<?php echo htmlspecialchars($user['avatar_path']); ?>" alt="Avatar" class="avatar">
            <?php else: ?>
                <img src="default-avatar.png" alt="Avatar" class="avatar">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($user['username']); ?></h1>
        </div>
        <div class="update-avatar-form">
            <?php if (isset($_SESSION['username']) && $_SESSION['username'] === $user['username']): ?>
                <form action="upload_avatar.php" method="POST" enctype="multipart/form-data">
                    <label for="avatar">Сменить аватар:</label>
                    <input type="file" id="avatar" name="avatar" required>
                    <button type="submit">Загрузить</button>
                </form>
            <?php endif; ?>
        </div>
        <h2>Посты</h2>
        <?php foreach ($posts as $post) {
            display_post($post);
        } ?>
        <h2>Комментарии</h2>
        <?php foreach ($comments as $comment) {
            display_comment($comment);
        } ?>
    </div>
</body>
</html>
