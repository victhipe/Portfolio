<?php
session_start();

$servername = "localhost";
$dbname = "Forum_DEX";
$dbusername = "root";
$dbpassword = "";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

require_once 'check_ban.php';

$ip_address = $_SERVER['REMOTE_ADDR'];
if (is_banned($conn, null, $ip_address)) {
    die("Ваш IP-адрес заблокирован.");
}

if (isset($_SESSION['user_id'])) {
    if (is_banned($conn, $_SESSION['user_id'])) {
        session_destroy();
        die("Ваш аккаунт заблокирован.");
    }
}

function display_comments($comments, $conn, $userId) {
    foreach ($comments as $comment) {
        echo "<div class='comment'>";
        echo "<p><strong><a href='profile.php?username=" . htmlspecialchars($comment['username']) . "'>" . htmlspecialchars($comment['username']) . "</a>:</strong> " . nl2br(htmlspecialchars($comment['content'])) . "</p>";

        $voteScore = getVoteScore($conn, $comment['id'], 'comment');
        $userVote = getUserVote($conn, $comment['id'], $userId, 'comment');

        if (isset($_SESSION['username'])) {
            echo "<div class='vote-buttons'>";
            echo "<button style='font-size: 22px; padding: 10px;' onclick='vote(" . $comment['id'] . ", \"up\", \"comment\")'" . ($userVote == 1 ? " disabled" : "") . ">👍</button>";
            echo "<span class='vote-score' style='font-size: 22px; margin: 0 10px;'>" . $voteScore . "</span>";
            echo "<button style='font-size: 22px; padding: 10px;' onclick='vote(" . $comment['id'] . ", \"down\", \"comment\")'" . ($userVote == -1 ? " disabled" : "") . ">👎</button>";
            echo "</div>";

           
            echo "<div>"; 
            echo "<a href='#' onclick='showReplyForm(" . $comment['id'] . ")'>Ответить</a>";

            if ($comment['username'] === $_SESSION['username']) {
                echo "<form action='delete_comment.php' method='POST' style='display:inline; margin-left: 10px;'>";
                echo "<input type='hidden' name='comment_id' value='" . htmlspecialchars($comment['id']) . "'>";
                echo "<button type='submit'>Удалить комментарий</button>";
                echo "</form>";
            }
            echo "</div>";

            echo "<div id='reply-form-" . $comment['id'] . "' style='display:none; margin-top: 10px;'>";
            echo "<form action='add_comment.php' method='POST'>";
            echo "<input type='hidden' name='post_id' value='" . htmlspecialchars($comment['post_id']) . "'>";
            echo "<input type='hidden' name='parent_id' value='" . htmlspecialchars($comment['id']) . "'>";
            echo "<textarea name='content' required></textarea>"; 
            echo "<button type='submit'>Ответить</button>";
            echo "</form>";
            echo "</div>";
        } else {
            echo "<div class='vote-buttons'>";
            echo "<button style='font-size: 22px; padding: 10px;' disabled>👍</button>";
            echo "<span class='vote-score' style='font-size: 22px; margin: 0 10px;'>" . $voteScore . "</span>";
            echo "<button style='font-size: 22px; padding: 10px;' disabled>👎</button>";
            echo "</div>";
        }

        $stmt = $conn->prepare("SELECT * FROM comments WHERE parent_id = :parent_id ORDER BY created_at DESC");
        $stmt->execute(['parent_id' => $comment['id']]);
        $replies = $stmt->fetchAll();

        if (count($replies) > 0) {
            echo "<div class='reply'>";
            display_comments($replies, $conn, $userId);
            echo "</div>";
        }

        echo "</div>";
    }
}

function getVoteScore($conn, $id, $type) {
    $stmt = $conn->prepare("SELECT SUM(vote) AS score FROM votes WHERE " . $type . "_id = :id");
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    return $result['score'] ?? 0;
}

function getUserVote($conn, $id, $userId, $type) {
    $stmt = $conn->prepare("SELECT vote FROM votes WHERE " . $type . "_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    $result = $stmt->fetch();
    return $result['vote'] ?? 0;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta name="description" content="Forum DEX - это место для обсуждения новейших тенденций в мире IT, технологий, программирования и инноваций. Присоединяйтесь к нашему сообществу!">
    <meta name="keywords" content="форум, IT, технологии, программирование, инновации, Forum DEX">
    <meta name="author" content="Forum DEX Team">
    <meta property="og:title" content="Forum DEX - IT форум">
    <meta property="og:description" content="Обсуждайте новейшие тенденции в мире IT, технологий и программирования на Forum DEX. Присоединяйтесь к нашему сообществу!">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <title>Главная страница</title>
    <style>
        
        A{
            color: white;
            text-decoration: none;
        }
        button {
            background-color: #5b83c1;
            color: white;
            font-size: 12px;
            border: none;
            border-radius: 5px;
            box-sizing: border-box;
            padding: 5px;
            margin: 5px;
        }
        body {
            margin: 0px;
            background-color: #5b83c1;
            font-family: 'Istok Web', sans-serif;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #6996FF;
            padding-left: 5%;
            padding-right: 5%;
        }
        .create-post-form-color {
            background-color: #355FC0;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            align-content: center;
            flex-direction: column;
        }
        .title-input {
            width: 500px;
            height: 25px;
            border-radius: 4px;
            border: none;
        }
        .header p {
            margin: 0;
            color: white;
        }
        .header form {
            margin: 0;
        }
        .container {
            padding: 20px;
        }
        .header-right {
            display: flex;
            justify-content: flex-end;
            flex-direction: row;
            align-items: center;
            position: relative;
        }
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 25%;
            margin-right: 10px;
            cursor: pointer;
        }
        .plus-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            margin-right: 5px;
        }
        .post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 25%;
            vertical-align: middle;
            margin-right: 2%;
        }
        .header-left{
            display: flex;
        }
        #upload-avatar-form{
            color: white;
            display: flex;
            justify-content: center;
            flex-direction: column;
            align-content: center;
            align-items: center;
        }
        .post {
            padding: 10px;
            margin-bottom: 10px;
            padding-left: 5%;
            padding-right: 5%;
            color: white;
            background-color: #6996FF;
            margin-left: 5%;
            margin-right: 5%;
            border-radius: 24px;
        }
        .post-up{
           color: white;
        }
        .post-title {
            display: flex;
            flex-direction: column;
            align-content: center;
            align-items: center;
            color: white;
        }
        .post-up {
            display: flex;
            align-content: center;
            justify-content: space-between;
            align-items: center;
        }
        .post-up-left {
            display: flex;
            align-items: center;
            color: white;
        }
        .dropdown {
            display: none;
            position: absolute;
            top: 60px;
            right: 0;
            background-color: #355FC1;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-radius: 15%;
            z-index: 1;
        }
        .dropdown a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .dropdown a:hover {
            background-color: #ddd;
        }
        .center-post-img-audio-video {   
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .search_style {
            border: none;
            border-bottom: 2px solid #fff;
            background: none;
            padding: 5px;
            outline: none;
            font-size: 16px;
            color: #fff;
        }
        .search_style::placeholder {
            color: #fff; 
        }
        #create-post-form {
            display: none;
        }
        .comment {
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .reply {
            margin-left: 20px;
            border-left: 1px solid #ddd;
            padding-left: 10px;
        }
        .style-content {
            height: 200px;
            width: 100%;
            margin-top: 10px;
            border-radius: 20px;
            border: none;
            margin-bottom: 10px;
        }
        .flex-comment {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            flex-direction: column;
        }
        .center-post-img-audio-video {
            display: flex;
            flex-direction: column;
            align-items: center;
            align-content: center;
        }
        .comment-button {
            background-color: #5b83c1;
            border: none;
            font-size: 14px;
            border-radius: 6px;
            box-sizing: border-box;
            padding: 5px;
            margin: 5px;
        }
        .vote-buttons {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .vote-score {
            margin: 0 10px;
        }
        .chat-button ,  .white-color-text{
            color: white;
        }

       .footer {
        background-color: #3c6199;
        margin-top: 64px;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    color: white;
}

      .social-media {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
}

       .social-media a img {
    width: 32px;
    height: 32px;
}

     .copyright {
    text-align: center;
    font-size: 14px;
}
.burger-menu {
    font-size: 24px;
    cursor: pointer;
    margin-right: 15px;
   color: white;
}

.section-menu {
    position: absolute;
    top: 60px;
    left: 10px;
    background-color: #355FC1;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    border-radius: 5px;
    z-index: 1;
}

.section-menu a {
    color: white;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}

.section-menu a:hover {
    background-color: #4671E0;
}
    </style>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function toggleCreatePostForm() {
            const form = document.getElementById('create-post-form');
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }

        function showReplyForm(commentId) {
  console.log("commentId:", commentId); 
  const form = document.getElementById('reply-form-' + commentId);
  console.log("form:", form); т
  if (form) {
    form.style.display = form.style.display === 'block' ? 'none' : 'block';
  } else {
    console.error("Форма не найдена!"); 
  }
}
function toggleBurgerMenu() {
    const menu = document.getElementById('section-menu');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}
        function updateCharacterCount() {
            const content = document.getElementById('title');
            const charCount = document.getElementById('charCount');
            charCount.textContent = content.value.length + "/128";
        }
        function updateCharacterCount() {
            const content = document.getElementById('content');
            const charCount = document.getElementById('charCount');
            charCount.textContent = content.value.length + "/1024";
        }

        function vote(id, direction, type) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "vote.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    location.reload();
                }
            };
            xhr.send("id=" + id + "&direction=" + direction + "&type=" + type);
        }
        function escapeHTML(text) {
    var element = document.createElement('div');
    element.innerText = text;
    return element.innerHTML;
}
        
    </script>
</head>
<body>
<?php
$servername = "localhost";
$dbname = "Forum_DEX";
$dbusername = "root";
$dbpassword = "";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_SESSION['username']) && isset($_SESSION['fullname'])) {
        $stmt = $conn->prepare("SELECT id, avatar_path, role FROM users WHERE username = :username");
        $stmt->execute(['username' => $_SESSION['username']]);
        $user = $stmt->fetch();

        $userId = $user['id'];
        $userRole = $user['role'];
        
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<div class="header">';
        echo '<div class="header-left">';
        echo '<div class="burger-menu" onclick="toggleBurgerMenu()">☰</div>';
        echo '<div id="section-menu" class="section-menu" style="display:none;">
                <a href="?section=все">Все</a>
                <a href="?section=тестирование">Тестирование</a>
                <a href="?section=программирование">Программирование</a>
                 <a href="?section=системная-аналика">Системная аналитика</a>
                 <a href="?section=Новости">Новости</a>
                <a href="?section=дизайн">Дизайн</a>
              </div>';
        echo '<form action="" method="GET">';
        echo '<input type="text" name="search" placeholder="Поиск" class="search_style">';
        echo '</form>';
        echo '</div>';
        echo '<div class="header-right">';
        echo '<a href="chat.php" class="chat-button">Chat</a>';  
        echo '<div class="plus-icon" onclick="toggleCreatePostForm()">+</div>';
        if (!empty($user['avatar_path'])) {
            echo "<img src='" . htmlspecialchars($user['avatar_path']) . "' alt='Avatar' class='avatar' onclick='toggleDropdown()'>";
        }

        echo "<a class='username' href='profile.php?username=" . htmlspecialchars($_SESSION['username']) . "'><p>" . htmlspecialchars($_SESSION['username']) . "</p></a>";
        echo '<div id="dropdown" class="dropdown">';
        echo '<a href="#" onclick="document.getElementById(\'upload-avatar-form\').style.display=\'block\'">Сменить аватарку</a>';
        echo '<a href="setting.php">Настройки</a>';

       
        if ($userRole === 'admin') {
            echo '<a href="admin_panel.php">Админ панель</a>';
        }

        echo '<a href="logout.php">Выйти</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<form id="upload-avatar-form" action="upload_avatar.php" method="POST" enctype="multipart/form-data" style="display:none;">';
        echo '<label for="avatar">Выберите аватар:</label>';
        echo '<input type="file" id="avatar" name="avatar" required>';
        echo '<button type="submit">Загрузить</button>';
        echo '</form>';

        echo '
            <div id="create-post-form" class="create-post-form-color">
        <form action="create_post.php" method="POST" enctype="multipart/form-data" style="display: block; display: flex; flex-direction: column; align-content: center; align-items: center; padding: 24px;">
            <p class="create-post-title"> Создание поста </p>
            <label for="title" class="title">Заголовок:</label>
            <input type="text" id="title" name="title" class="title-input" required><br>
            <label for="content">Контент:</label>
            <input id="content" name="content" class="style-content" maxlength="1024" oninput="updateCharacterCount()" required>
            <label for="section">Раздел:</label>
            <select name="section" id="section" required>
                <option value="все">Все</option>
                <option value="тестирование">Тестирование</option>
                <option value="программирование">Программирование</option>
                <option value="системная-аналитика">Системная аналитика</option>
                <option value="дизайн">Дизайн</option>
                 <option value="новости">Новости</option>
            </select><br>
                    <div class="center-post-img-audio-video">
                    <label for="image">Изображение:</label>
                    <input type="file" id="image" name="image"><br>
                    <div class="center-post-img-audio-video">
                    <label for="audio">Аудио:</label>
                    <input type="file" id="audio" name="audio"><br>
                    <div class="center-post-img-audio-video">
                    <label for="video">Видео:</label>
                    <input type="file" id="video" name="video"><br>
                    </div>
                    </div>
                    </div>
                    <button type="submit" style="width: 80px; height: 48px; border: none; border-radius: 10px;">Создать пост</button>
                </form>
            </div>
        ';
    } else {
        echo "<div class='header'>";
        echo '<div class="header-left">';
        echo '<form action="" method="GET">';
        echo '<input type="text" name="search" placeholder="Поиск" class="search_style">';
        echo '</form>';
        echo '</div>';
        echo '<div class="header-right">';
        echo '<a href="login.php" class="chat-button">Войти</a>';
        echo '</div>';
        echo '</div>';
    }

    $section = isset($_GET['section']) ? $_GET['section'] : 'все';
$search = isset($_GET['search']) ? $_GET['search'] : '';

if (!empty($search)) {
    if ($section === 'все') {
        $stmt = $conn->prepare("SELECT posts.*, users.avatar_path FROM posts INNER JOIN users ON posts.username = users.username WHERE (posts.title LIKE :search OR posts.username LIKE :search) ORDER BY posts.created_at DESC");
        $stmt->execute(['search' => '%' . $search . '%']);
    } else {
        $stmt = $conn->prepare("SELECT posts.*, users.avatar_path FROM posts INNER JOIN users ON posts.username = users.username WHERE (posts.title LIKE :search OR posts.username LIKE :search) AND posts.section = :section ORDER BY posts.created_at DESC");
        $stmt->execute(['search' => '%' . $search . '%', 'section' => $section]);
    }
} else {
    if ($section === 'все') {
        $stmt = $conn->prepare("SELECT posts.*, users.avatar_path FROM posts INNER JOIN users ON posts.username = users.username ORDER BY posts.created_at DESC");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT posts.*, users.avatar_path FROM posts INNER JOIN users ON posts.username = users.username WHERE posts.section = :section ORDER BY posts.created_at DESC");
        $stmt->execute(['section' => $section]);
    }
}
$posts = $stmt->fetchAll();
    echo "<h2 class='post-title'>Посты</h2>";

    foreach ($posts as $post) {
        echo "<div class='post'>";
        echo "<div class='post-up'>";
        echo "<div class='post-up-left'>";
        if (!empty($post['avatar_path'])) {
            echo "<a href='profile.php?username=" . htmlspecialchars($post['username']) . "'><img src='" . htmlspecialchars($post['avatar_path']) . "' alt='Avatar' class='post-avatar'></a>";
        }
        echo "<a class='white-color-text' href='profile.php?username=" . htmlspecialchars($post['username']) . "'><p>" . htmlspecialchars($post['username']) . "</p></a>";
        echo "</div>";
        
        if (isset($_SESSION['username'])) {
            echo "<form action='delete_post.php' method='POST' style='display:inline;'>";
            echo "<input type='hidden' name='post_id' value='" . htmlspecialchars($post['id']) . "'>";
            echo "<button type='submit'>Удалить пост</button>";
            echo "</form>";
        }
        echo "</div>";

    
        $voteScore = getVoteScore($conn, $post['id'], 'post');

        $userVote = null;
        if (isset($_SESSION['username']) && isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId']; 
            $userVote = getUserVote($conn, $post['id'], $userId, 'post');
        }
        
        echo "<div class='vote-buttons'>";
        if (isset($_SESSION['username'])) {
            $userVote = isset($userVote) ? $userVote : 0; 
            echo "<button onclick='vote(" . $post['id'] . ", \"up\", \"post\")' style='font-size: 22px;'" . ($userVote == 1 ? " disabled" : "") . ">👍</button>";
            echo "<span class='vote-score'>" . $voteScore . "</span>";
            echo "<button onclick='vote(" . $post['id'] . ", \"down\", \"post\")' style='font-size: 22px;'" . ($userVote == -1 ? " disabled" : "") . ">👎</button>";
        } else {
            echo "<button style='font-size: 22px;' disabled>👍</button>";
            echo "<span class='vote-score'>" . $voteScore . "</span>";
            echo "<button style='font-size: 22px;' disabled>👎</button>";
        }
        echo "</div>";
        

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

        $stmt = $conn->prepare("SELECT * FROM comments WHERE post_id = :post_id AND parent_id IS NULL ORDER BY created_at DESC");
        $stmt->execute(['post_id' => $post['id']]);
        $comments = $stmt->fetchAll();

        if (count($comments) > 0) {
            display_comments($comments, $conn, isset($_SESSION['username']) ? $userId : null);
        }
        
        if (isset($_SESSION['username'])) {
            echo "<form action='add_comment.php' method='POST'>";
            echo "<div class ='flex-comment'>";
            echo "<input type='hidden' name='post_id' value='" . htmlspecialchars($post['id']) . "'>";
            echo "<input name='content' class='style-content' required>";
            echo "<button type='submit' class='comment-button'>Комментировать</button>";
            echo "</form>";
            echo "</div>";
        } else {
            echo "<p>Чтобы оставить комментарий, пожалуйста, <a href='login.php'>войдите</a>.</p>";
        }

        echo "</div>"; 
    }
    echo '<footer class="footer">';
    echo '    <div class="social-media">';
    echo '        <a href="#"><img src="Logo/vk.png" alt="VK"></a>';
    echo '        <a href="#"><img src="Logo/telegram.png" alt="Telegram"></a>';
    echo '        <a href="#"><img src="Logo/x.png" alt="X"></a>';
    echo '        <a href="#"><img src="Logo/youtube.png" alt="YouTube"></a>';
    echo '    </div>';
    echo '    <div class="support">';
    echo '        <a href="technical_support.php">Связаться с техподдержкой</a>';
    echo '    </div>';
    echo '    <div class="copyright">';
    echo '        &copy; 2010-2024, Forum Dex';
    echo '    </div>';
    echo '</footer>';
    

} catch(PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}

$conn = null;
?>
</body>
</html>
