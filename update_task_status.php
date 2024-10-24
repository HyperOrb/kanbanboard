<?php
session_start();

// Redirect to login if the user is not authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Database connection
$dsn = "mysql:host=localhost;dbname=todo_list";
$pdo = new PDO($dsn, "root", "");

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['task_id']) && isset($_POST['status'])) {
        $task_id = $_POST['task_id'];
        $status = $_POST['status'];
        $user_id = $_SESSION['user_id'];

        // Update task status in the database
        $sql = "UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$status, $task_id, $user_id]);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to update task status']);
        }
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request method']);
?>
