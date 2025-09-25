<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$id = $_POST['id'];
$direction = $_POST['direction'];
$type = $_POST['type'];

if (!in_array($direction, ['up', 'down']) || !in_array($type, ['post', 'comment'])) {
    echo json_encode(['error' => 'Invalid vote']);
    exit;
}

$vote_value = ($direction === 'up') ? 1 : -1;
$column = ($type === 'post') ? 'post_id' : 'comment_id';

try {
    $conn = new PDO("mysql:host=localhost;dbname=Forum_DEX", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT vote FROM votes WHERE $column = :id AND user_id = :user_id");
    $stmt->execute(['id' => $id, 'user_id' => $user_id]);
    $existing_vote = $stmt->fetch();

    if ($existing_vote) {
        if ($existing_vote['vote'] == $vote_value) {
            $stmt = $conn->prepare("DELETE FROM votes WHERE $column = :id AND user_id = :user_id");
            $stmt->execute(['id' => $id, 'user_id' => $user_id]);
        } else {
            $stmt = $conn->prepare("UPDATE votes SET vote = :vote WHERE $column = :id AND user_id = :user_id");
            $stmt->execute(['vote' => $vote_value, 'id' => $id, 'user_id' => $user_id]);
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO votes (user_id, $column, vote) VALUES (:user_id, :id, :vote)");
        $stmt->execute(['user_id' => $user_id, 'id' => $id, 'vote' => $vote_value]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

