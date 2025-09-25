<?php
session_start();

// Функция для подключения к базе данных
function connectDB() {
    $conn = new mysqli('localhost', 'root', '', 'Forum_Dex');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Функция для проверки существования пользователя
function userExists($conn, $username) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Функция для регистрации пользователя
function registerUser($conn, $username, $password, $fullname, $gender) {
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $avatar_path = 'avatars/avatar.png';
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, avatar_path, gender) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hashed_password, $fullname, $avatar_path, $gender);
    
    $success = $stmt->execute();
    if (!$success) {
        error_log("SQL Error: " . $stmt->error);
    }
    $stmt->close();
    return $success;
}
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $gender = $_POST['gender'] ?? '';

    if (empty($username) || empty($password) || empty($confirm_password) || empty($fullname) || empty($gender)) {
        $error_message = "Все поля должны быть заполнены.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Пароли не совпадают.";
    } else {
        $conn = connectDB();
        
        if (userExists($conn, $username)) {
            $error_message = "Этот никнейм уже занят.";
        } else {
            if (registerUser($conn, $username, $password, $fullname, $gender)) {
                $_SESSION['success_message'] = "Аккаунт зарегистрирован.";
                header('Location: login.php');
                exit();
            } else {
                $error_message = "Ошибка при регистрации пользователя.";
            }
        }
        
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #B1D0FF;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .registration-form {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        .registratorr-main {
            color: #3b82f6;
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }
        .registration-form label {
            display: block;
            margin-bottom: 5px;
            color: #3b82f6;
            font-weight: 600;
        }
        .registration-form input[type="text"],
        .registration-form input[type="password"],
        .registration-form input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 20px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        .registration-form input[type="submit"],
        .flex-button-onclick button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 20px;
            background-color: #3b82f6;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }
        .registration-form input[type="submit"]:hover,
        .flex-button-onclick button:hover {
            background-color: #2563eb;
        }
        .custom-radio {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .custom-radio label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .custom-radio input[type="radio"] {
            margin-right: 5px;
        }
        #agreement-error,
        #username-error,
        #password-error {
            color: #dc2626;
            font-size: 14px;
            margin-top: -10px;
            margin-bottom: 10px;
        }
        #success-message {
            color: #10b981;
            text-align: center;
            margin-top: 20px;
            font-weight: 600;
        }
        .flex-button-onclick {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .flex-button-onclick button {
            flex: 1;
            background-color: #4b5563;
        }
        .flex-button-onclick button:hover {
            background-color: #374151;
        }
    </style>
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#username').blur(function() {
                var username = $(this).val();
                $.ajax({
                    url: 'check_username.php',
                    method: 'POST',
                    data: { username: username },
                    success: function(response) {
                        if (response == 'taken') {
                            $('#username-error').text('Этот никнейм уже занят').show();
                        } else {
                            $('#username-error').hide();
                        }
                    }
                });
            });

            $('#registration-form').submit(function(e) {
                e.preventDefault();

                if (!$('#agreement').is(':checked')) {
                    $('#agreement-error').text('Чтобы продолжить, ознакомьтесь с условиями соглашения и подтвердите своё согласие.').show();
                    return;
                } else {
                    $('#agreement-error').hide();
                }

                var password = $('#password').val();
                var confirmPassword = $('#confirm_password').val();
                if (password !== confirmPassword) {
                    $('#password-error').text('Пароли не совпадают').show();
                    return;
                } else {
                    $('#password-error').hide();
                }

                this.submit();
            });
        });
    </script>
</head>
<body>
    <div class="registration-form">
        <h2 class="registratorr-main">Регистрация</h2>
        <?php
        if ($error_message) {
            echo "<p style='color: red;'>$error_message</p>";
        }
        if ($success_message) {
            echo "<p style='color: green;'>$success_message</p>";
        }
        ?>
        <form id="registration-form" method="post">
            <label for="username">Логин:</label>
            <input type="text" id="username" name="username" required>
            <div id="username-error"></div>
            
            <label for="password">Придумайте пароль:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="confirm_password">Введите снова пароль:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <div id="password-error"></div>
            
            <label for="fullname">ФИО:</label>
            <input type="text" id="fullname" name="fullname" required>
            
            <label>Пол:</label>
            <div class="custom-radio">
                <label>
                    <input type="radio" name="gender" value="male" required> Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female" required> Женский
                </label>
            </div>
            
            <label>
                <input type="checkbox" id="agreement" name="agreement" required>
                Я прочитал(а) и принимаю <a href="User-Agreement.php" target="_blank">Пользовательское соглашение</a>
            </label>
            <div id="agreement-error"></div>
        
            <input type="submit" value="Зарегистрироваться">
        </form>
        
        <div class="flex-button-onclick">
            <button onclick="window.location.href='login.php'">Есть аккаунт</button>
            <button onclick="window.location.href='index.php'">На главную</button>
        </div>
    </div>
</body>
</html>