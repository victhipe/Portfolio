<?php
$servername = "localhost";
$dbname = "Forum_DEX";
$dbusername = "root";
$dbpassword = "";

// Create connection
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_email = $_POST['email'];
    $issue_description = $_POST['issue_description'];

    $sql = "INSERT INTO support_requests (email, issue_description) VALUES (?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user_email, $issue_description);

    if ($stmt->execute()) {
        $success_message = "Ваш запрос отправлен. Мы свяжемся с вами в ближайшее время.";
    } else {
        $error_message = "Ошибка при отправке запроса: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Связаться с техподдержкой</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #5b83c1;
            margin: 0;
            padding: 0;
            color: white;
        }
        .container {
            background-color: #6996FF;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            color: white;
            border-radius: 8px;
        }
        h1 {
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-top: 10px;
            font-weight: bold;
        }
        input[type="email"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            resize: vertical;
            height: 150px;
        }
        input[type="submit"] {
            margin-top: 20px;
            padding: 10px;
            background-color: #28a745;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .message {
            text-align: center;
            font-size: 16px;
        }
        .error {
            text-align: center;
            font-size: 16px;
        }
    </style>
</head>
<body>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <div class="container">
        <h1>Связаться с техподдержкой</h1>
        <?php if (isset($success_message)): ?>
            <p class="message"><?php echo $success_message; ?></p>
        <?php elseif (isset($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="technical_support.php" method="post">
            <label for="email">Ваш Email:</label>
            <input type="email" id="email" name="email" required>
            <label for="issue_description">Описание проблемы:</label>
            <textarea id="issue_description" name="issue_description" required></textarea>
            <input type="submit" value="Отправить">
        </form>
    </div>
</body>
</html>
