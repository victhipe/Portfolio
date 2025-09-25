<?php
session_start();

$servername = "127.0.0.1";
$db_username = "root"; 
$db_password = ""; 
$dbname = "forum_dex";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $admin_user = 'admin'; 
        $admin_pass = 'password'; 

        if ($_POST['username'] == $admin_user && $_POST['password'] == $admin_pass) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin_panel.php");
            exit;
        } else {
            echo "Неверный логин или пароль";
        }
    }
?>
    <form method="post">
        <label>Никнейм: <input type="text" name="username"></label><br>
        <label>Пароль: <input type="password" name="password"></label><br>
        <input type="submit" value="Login">
    </form>
<?php
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_panel.php");
    exit;
}
if (isset($_POST['action']) && $_POST['action'] == 'ban_user' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $reason = $conn->real_escape_string($_POST['reason']);
    $duration = intval($_POST['duration']);
    $expiration_date = $duration > 0 ? date('Y-m-d H:i:s', strtotime("+$duration days")) : NULL;

    $stmt = $conn->prepare("INSERT INTO bans (user_id, reason, expiration_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $reason, $expiration_date);
    $stmt->execute();
    $stmt->close();
}

if (isset($_GET['action']) && $_GET['action'] == 'unban_user' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $conn->query("DELETE FROM bans WHERE user_id = $user_id");
}

if (isset($_POST['action']) && $_POST['action'] == 'edit_user' && isset($_POST['id']) && isset($_POST['username']) && isset($_POST['fullname'])) {
    $user_id = intval($_POST['id']);
    $username = $conn->real_escape_string($_POST['username']);
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $conn->query("UPDATE users SET username = '$username', fullname = '$fullname' WHERE id = $user_id");
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_user' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $conn->query("DELETE FROM users WHERE id = $user_id");
}


if (isset($_POST['action']) && $_POST['action'] == 'edit_post' && isset($_POST['id']) && isset($_POST['title']) && isset($_POST['content'])) {
    $post_id = intval($_POST['id']);
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $conn->query("UPDATE posts SET title = '$title', content = '$content' WHERE id = $post_id");
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_post' && isset($_GET['id'])) {
    $post_id = intval($_GET['id']);
    $conn->query("DELETE FROM posts WHERE id = $post_id");
}


if (isset($_POST['action']) && $_POST['action'] == 'edit_comment' && isset($_POST['id']) && isset($_POST['content'])) {
    $comment_id = intval($_POST['id']);
    $content = $conn->real_escape_string($_POST['content']);
    $conn->query("UPDATE comments SET content = '$content' WHERE id = $comment_id");
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_comment' && isset($_GET['id'])) {
    $comment_id = intval($_GET['id']);
    $conn->query("DELETE FROM comments WHERE id = $comment_id");
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_request' && isset($_GET['id'])) {
    $request_id = intval($_GET['id']);
    $conn->query("DELETE FROM support_requests WHERE id = $request_id");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ Панель</title>
    <style>
        body {
            background-color: #5b83c1;
            font-family: 'Istok Web', sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            background-color: #6996FF;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #355FC0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            color: white;
        }
        th {
            background-color: #355FC0;
            color: white;
        }
        .form-edit {
            display: none;
            margin-bottom: 20px;
        }
        .form-edit input[type="text"], .form-edit textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-edit input[type="submit"] {
            padding: 10px 20px;
            background-color: #5cb85c;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
        }
        .form-edit input[type="submit"]:hover {
            background-color: #4cae4c;
        }
        .edit-btn, .delete-btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
            color: #fff;
        }
        .edit-btn {
            background-color: #0275d8;
        }
        .edit-btn:hover {
            background-color: #025aa5;
        }
        .delete-btn {
            background-color: #d9534f;
        }
        .delete-btn:hover {
            background-color: #c9302c;
        }
        .ban-btn, .unban-btn {
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 5px;
    color: #fff;
    margin-left: 5px;
}
.ban-btn {
    background-color: #f0ad4e;
}
.ban-btn:hover {
    background-color: #ec971f;
}
.unban-btn {
    background-color: #5bc0de;
}
.unban-btn:hover {
    background-color: #31b0d5;
}
    </style>
    <script>
        function editUser(id, username, fullname) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUsername').value = username;
            document.getElementById('editFullname').value = fullname;
            document.getElementById('editUserForm').style.display = 'block';
        }

        function editPost(id, title, content) {
            document.getElementById('editPostId').value = id;
            document.getElementById('editPostTitle').value = title;
            document.getElementById('editPostContent').value = content;
            document.getElementById('editPostForm').style.display = 'block';
        }

        function editComment(id, content) {
            document.getElementById('editCommentId').value = id;
            document.getElementById('editCommentContent').value = content;
            document.getElementById('editCommentForm').style.display = 'block';
        }
        function banUser(id, username) {
            document.getElementById('banUserId').value = id;
            document.getElementById('banUsername').textContent = username;
            document.getElementById('banUserForm').style.display = 'block';
        }
        
    </script>
</head>
<body>
    <div class="container">
        <h1>Админ Панель</h1>
        <a href="?logout=true">Выйти</a>
        
        <h2>Пользователи</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Никнейм</th>
        <th>ФИО</th>
        <th>Статус</th>
        <th>Действия</th>
    </tr>
    <?php
    $result = $conn->query("SELECT users.*, bans.id AS ban_id, bans.expiration_date 
                            FROM users 
                            LEFT JOIN bans ON users.id = bans.user_id");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['username']}</td>";
        echo "<td>{$row['fullname']}</td>";
        echo "<td>" . ($row['ban_id'] ? ($row['expiration_date'] ? "Забанен до {$row['expiration_date']}" : "Забанен навсегда") : "Активен") . "</td>";
        echo "<td>
                <a href='#' class='edit-btn' onclick='editUser({$row['id']}, \"{$row['username']}\", \"{$row['fullname']}\")'>Изменить</a>
                <a href='?action=delete_user&id={$row['id']}' class='delete-btn'>Удалить</a>";
        if (!$row['ban_id']) {
            echo "<a href='#' class='ban-btn' onclick='banUser({$row['id']}, \"{$row['username']}\")'>Забанить</a>";
        } else {
            echo "<a href='?action=unban_user&id={$row['id']}' class='unban-btn'>Разбанить</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    ?>
</table>
<div id="banUserForm" class="form-edit">
    <h3>Забанить пользователя</h3>
    <form method="post">
        <input type="hidden" name="user_id" id="banUserId">
        <p>Забанить пользователя: <span id="banUsername"></span></p>
        <textarea name="reason" placeholder="Причина бана" required></textarea>
        <input type="number" name="duration" placeholder="Длительность бана в днях (0 для вечного бана)" required>
        <input type="submit" value="Забанить">
        <input type="hidden" name="action" value="ban_user">
    </form>
</div>
        </table>
        <div id="editUserForm" class="form-edit">
            <h3>Изменить пользователя</h3>
            <form method="post">
                <input type="hidden" name="id" id="editUserId">
                <input type="text" name="username" id="editUsername" placeholder="Никнейм">
                <input type="text" name="fullname" id="editFullname" placeholder="ФИО">
                <input type="submit" value="Сохранить">
                <input type="hidden" name="action" value="edit_user">
            </form>
        </div>

        <h2>Посты</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Никнейм</th>
                <th>Описание</th>
                <th>Контент</th>
                <th>Действия</th>
            </tr>
            <?php
            $result = $conn->query("SELECT * FROM posts");
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['username']}</td>";
                echo "<td>{$row['title']}</td>";
                echo "<td>{$row['content']}</td>";
                echo "<td>
                        <a href='#' class='edit-btn' onclick='editPost({$row['id']}, \"{$row['title']}\", \"{$row['content']}\")'>Изменить</a>
                        <a href='?action=delete_post&id={$row['id']}' class='delete-btn'>Удалить</a>
                      </td>";
                echo "</tr>";
            }
            ?>
        </table>
        <div id="editPostForm" class="form-edit">
            <h3>Редактировать посты</h3>
            <form method="post">
                <input type="hidden" name="id" id="editPostId">
                <input type="text" name="title" id="editPostTitle" placeholder="Описание">
                <textarea name="content" id="editPostContent" placeholder="Контент"></textarea>
                <input type="submit" value="Сохранить">
                <input type="hidden" name="action" value="edit_post">
            </form>
        </div>

        <h2>Комментарии</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Никнейм</th>
                <th>Контент</th>
                <th>Действия</th>
            </tr>
            <?php
            $result = $conn->query("SELECT * FROM comments");
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['username']}</td>";
                echo "<td>{$row['content']}</td>";
                echo "<td>
                        <a href='#' class='edit-btn' onclick='editComment({$row['id']}, \"{$row['content']}\")'>Изменить</a>
                        <a href='?action=delete_comment&id={$row['id']}' class='delete-btn'>Удалить</a>
                      </td>";
                echo "</tr>";
            }
            ?>
        </table>
        <div id="editCommentForm" class="form-edit">
            <h3>Редактировать комментарий</h3>
            <form method="post">
                <input type="hidden" name="id" id="editCommentId">
                <textarea name="content" id="editCommentContent" placeholder="Контент"></textarea>
                <input type="submit" value="Сохранить">
                <input type="hidden" name="action" value="edit_comment">
            </form>
        </div>

        <h2>Запросы в техподдержку</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Описание проблемы</th>
                <th>Дата создания</th>
                <th>Действия</th>
            </tr>
            <?php
            $result = $conn->query("SELECT * FROM support_requests");
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['email']}</td>";
                echo "<td>{$row['issue_description']}</td>";
                echo "<td>{$row['created_at']}</td>";
                echo "<td>
                        <a href='?action=delete_request&id={$row['id']}' class='delete-btn'>Удалить</a>
                      </td>";
                echo "</tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>
