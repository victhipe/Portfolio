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

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
            $filename = $_FILES['avatar']['name'];
            $filetype = $_FILES['avatar']['type'];
            $filesize = $_FILES['avatar']['size'];

            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!array_key_exists($ext, $allowed)) {
                throw new Exception("Ошибка: Некорректный формат файла.");
            }

            if ($filesize > 5 * 1024 * 1024) {
                throw new Exception("Ошибка: Размер файла превышает 5 МБ.");
            }

            if (in_array($filetype, $allowed)) {
                $new_filename = uniqid() . "." . $ext;
                $destination = "avatars/" . $new_filename;

                // Проверка и создание директории
                if (!is_dir('avatars')) {
                    mkdir('avatars', 0777, true);
                }

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                    $stmt = $conn->prepare("UPDATE users SET avatar_path = :avatar_path WHERE username = :username");
                    $stmt->execute(['avatar_path' => $destination, 'username' => $_SESSION['username']]);
                    $_SESSION['avatar_path'] = $destination;
                    header("Location: index.php");
                } else {
                    throw new Exception("Ошибка: Не удалось сохранить файл.");
                }
            } else {
                throw new Exception("Ошибка: Некорректный формат файла.");
            }
        } else {
            throw new Exception("Ошибка: Не удалось загрузить файл.");
        }
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    $conn = null;
} else {
    echo "Вы не авторизованы.";
}
?>
