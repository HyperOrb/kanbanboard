<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$dsn = "mysql:host=localhost;dbname=todo_list";
$pdo = new PDO($dsn, "root", "");

$user_id = $_SESSION['user_id'];
$sql = "SELECT username, email FROM login_user WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode($user);
} else {
    echo json_encode(['error' => 'User not found']);
}
?>
