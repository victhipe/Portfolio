<!DOCTYPE html>
<html>
<head>
    <title>Авторизация</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .login-form {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        .login-form h2 {
            color: #3b82f6;
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }
        .login-form label {
            display: block;
            margin-bottom: 5px;
            color: #3b82f6;
            font-weight: 600;
        }
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 20px;
            border: 1px solid #ccc;
            font-size: 16px;
            color: #2563eb;
        }
        .login-form input[type="submit"],
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
        .login-form input[type="submit"]:hover,
        .flex-button-onclick button:hover {
            background-color: #2563eb;
        }
        .register-link {
            text-align: center;
            margin-top: 15px;
            color: #4b5563;
        }
        .register-link a {
            color: #3b82f6;
            text-decoration: none;
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
        .error-message {
            color: #dc2626;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Авторизация</h2>
        <form method="post" action="login.php">
            <label for="username">Логин:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>
            
            <input type="submit" value="Войти">
        </form>
        <div class="register-link">
            Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>
        </div>
        <div class="flex-button-onclick">
            <button onclick="window.location.href='index.php'">На главную</button>
            <button onclick="window.location.href='register.php'">Регистрация</button>
        </div>

        <?php
        session_start();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            $conn = new mysqli('localhost', 'root', '', 'Forum_Dex');

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $stmt = $conn->prepare("SELECT id, password, fullname FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($id, $hashed_password, $fullname);
            $stmt->fetch();

            if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['fullname'] = $fullname;
                header("Location: index.php");
                exit();
            } else {
                echo "<div class='error-message'>Неверный логин или пароль.</div>";
            }

            $stmt->close();
            $conn->close();
        }
        ?>
    </div>
</body>
</html>