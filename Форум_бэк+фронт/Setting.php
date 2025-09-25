<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$dbname = "Forum_DEX";
$dbusername = "root";
$dbpassword = "";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_SESSION['username'];

        if (isset($_POST['fullname'])) {
            $stmt = $conn->prepare("UPDATE users SET fullname = :fullname WHERE username = :username");
            $stmt->execute(['fullname' => $_POST['fullname'], 'username' => $username]);
            $_SESSION['fullname'] = $_POST['fullname'];
        }

        if (isset($_POST['new_username'])) {
            $stmt = $conn->prepare("UPDATE users SET username = :new_username WHERE username = :username");
            $stmt->execute(['new_username' => $_POST['new_username'], 'username' => $username]);
            $_SESSION['username'] = $_POST['new_username'];
            $username = $_POST['new_username']; // обновляем текущий username
        }

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
            $avatar_path = 'avatars/' . basename($_FILES['avatar']['name']);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path);

            $stmt = $conn->prepare("UPDATE users SET avatar_path = :avatar_path WHERE username = :username");
            $stmt->execute(['avatar_path' => $avatar_path, 'username' => $username]);
        }

        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE username = :username");
            $stmt->execute(['password' => $hashed_password, 'username' => $username]);
        }

        echo "<p>Настройки успешно обновлены!</p>";
    }
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Настройки профиля</title>
    <style>
        A {
            color: black;
            text-decoration: none;
        }
        button {
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
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
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
        #upload-avatar-form {
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
            width: 500px;
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
            background-color: white;
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
        .settings-container {
            background-color: #6996FF;
            padding: 20px;
            border-radius: 10px;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 50%;
        }
        .settings-container h2 {
            margin-bottom: 20px;
        }
        .settings-container label {
            display: block;
            margin-top: 10px;
        }
        .settings-container input {
            padding: 5px;
            border-radius: 5px;
            border: none;
            margin-top: 5px;
        }
        .settings-container button {
            margin-top: 20px;
        }
        .settings-container a {
            color: white;
            text-decoration: underline;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <h2>Настройки профиля</h2>
        <form action="setting.php" method="POST" enctype="multipart/form-data">
            <label for="fullname">Полное имя:</label>
            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($_SESSION['fullname']); ?>"><br>

            <label for="new_username">Никнейм:</label>
            <input type="text" id="new_username" name="new_username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>"><br>

            <label for="avatar">Аватар:</label>
            <input type="file" id="avatar" name="avatar"><br>

            <label for="password">Новый пароль:</label>
            <input type="password" id="password" name="password"><br>

            <button type="submit">Сохранить изменения</button>
        </form>
        <a href="index.php">На главную</a>
    </div>
</body>
</html>
