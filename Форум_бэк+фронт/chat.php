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
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

function fetchRooms($conn) {
    $stmt = $conn->prepare("SELECT * FROM rooms");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchMessages($conn, $roomId) {
    $stmt = $conn->prepare("SELECT messages.*, users.username FROM messages JOIN users ON messages.user_id = users.id WHERE room_id = :room_id ORDER BY created_at ASC");
    $stmt->execute(['room_id' => $roomId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addMessage($conn, $roomId, $userId, $content) {
    $stmt = $conn->prepare("INSERT INTO messages (room_id, user_id, content) VALUES (:room_id, :user_id, :content)");
    $stmt->execute(['room_id' => $roomId, 'user_id' => $userId, 'content' => $content]);
}

function fetchRoomMembers($conn, $roomId) {
    $stmt = $conn->prepare("SELECT users.username FROM room_members JOIN users ON room_members.user_id = users.id WHERE room_members.room_id = :room_id");
    $stmt->execute(['room_id' => $roomId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addRoomMember($conn, $roomId, $userId) {
    $stmt = $conn->prepare("INSERT INTO room_members (room_id, user_id) VALUES (:room_id, :user_id)");
    $stmt->execute(['room_id' => $roomId, 'user_id' => $userId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $roomName = $_POST['room_name'];
    $stmt = $conn->prepare("INSERT INTO rooms (name) VALUES (:name)");
    $stmt->execute(['name' => $roomName]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $roomId = $_POST['room_id'];
    $userId = $_SESSION['user_id'];
    $content = $_POST['content'];
    addMessage($conn, $roomId, $userId, $content);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $roomId = $_POST['room_id'];
    $username = $_POST['username'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        addRoomMember($conn, $roomId, $user['id']);
    } else {
        $error = "Пользователь не найден";
    }
}

$rooms = fetchRooms($conn);
$currentRoomId = isset($_GET['room_id']) ? $_GET['room_id'] : (count($rooms) > 0 ? $rooms[0]['id'] : null);
$messages = $currentRoomId ? fetchMessages($conn, $currentRoomId) : [];
$roomMembers = $currentRoomId ? fetchRoomMembers($conn, $currentRoomId) : [];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat</title>
    <style>
        body {
            font-family: 'Istok Web', sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .create_room{
            background-color: #355FC0;
        }
        .chat-container {
            display: flex;
            height: 100vh;
        }
        .rooms {
            width: 20%;
            background-color: #6996FF;
            padding: 20px;
            box-sizing: border-box;
        }
        .rooms h2 {
            color: white;
        }
        .messages {
            width: 60%;
            padding: 20px;
            box-sizing: border-box;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .members {
            width: 20%;
            background-color: #EEEEEE;
            padding: 20px;
            box-sizing: border-box;
        }
        .message-list {
            flex-grow: 1;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 10px;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .message p {
            margin: 0;
        }
        .send-message-form {
            display: flex;
        }
        .send-message-form input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
        }
        .send-message-form button {
            padding: 10px;
            background-color: #6996FF;
            border: none;
            color: white;
            border-radius: 0 5px 5px 0;
        }
        .add_new-form {
            margin-top: 20px;
        }
        .add_new-form input[type="text"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
        }
        .add_new-form button {
            padding: 10px;
            background-color: #6996FF;
            border: none;
            color: white;
            border-radius: 0 5px 5px 0;
        }
        
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="rooms">
            <h2>Комнаты</h2>
            <ul>
                <?php foreach ($rooms as $room): ?>
                    <li><a href="chat.php?room_id=<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <form method="POST" action=""  class="add_new-form" >
                <input type="text" name="room_name" placeholder="Имя комнаты" required>
                <button type="submit" name="create_room" class="create_room">Создать комнату</button>
            </form>
        </div>
        <div class="messages">
            <div class="message-list">
                <?php foreach ($messages as $message): ?>
                    <div class="message">
                        <strong><?= htmlspecialchars($message['username']) ?>:</strong>
                        <p><?= htmlspecialchars($message['content']) ?></p>
                        <small><?= $message['created_at'] ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($currentRoomId): ?>
                <form class="send-message-form " method="POST" action="">
                    <input type="hidden" name="room_id" value="<?= $currentRoomId ?>">
                    <input type="text" name="content" placeholder="Введите сообщение" required>
                    <button type="submit" name="send_message">Отправить</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="members">
            <h2>Участники комнаты</h2>
            <ul>
                <?php foreach ($roomMembers as $member): ?>
                    <li><?= htmlspecialchars($member['username']) ?></li>
                <?php endforeach; ?>
            </ul>
            <form class="add_new-form" method="POST" action="">
                <input type="hidden" name="room_id" value="<?= $currentRoomId ?>">
                <input type="text" name="username" placeholder="Имя пользователя" required>
                <button type="submit" name="add_member">Добавить участника</button>
                <?php if (isset($error)): ?>
                    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>
