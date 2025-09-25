<?php
$conn = new mysqli('localhost', 'root', '', 'Forum_Dex');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_POST['username'];

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    echo 'taken';
} else {
    echo 'available';
}

$stmt->close();
$conn->close();