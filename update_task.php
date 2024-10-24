<?php
session_start();

// Redirect to login if the user is not authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$dsn = "mysql:host=localhost;dbname=todo_list";
$pdo = new PDO($dsn, "root", "");

// Handle form submission for updating the task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $status = $_POST['status'] ?? 'To do';
    $due_date = $_POST['due_date'];
    $participants = $_POST['participants'];
    $user_id = $_SESSION['user_id'];

    // Update task in the database
    $sql = "UPDATE tasks SET title = ?, description = ?, status = ?, due_date = ?, participants = ? WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$title, $description, $status, $due_date, $participants, $task_id, $user_id]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update task']);
    }
}
?>
