<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$dsn = "mysql:host=localhost;dbname=todo_list";
$pdo = new PDO($dsn, "root", "");

// Fetch tasks for the logged-in user
$sql = "SELECT * FROM tasks WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group tasks by status
$grouped_tasks = [
    'Done' => []
];
foreach ($tasks as $task) {
    $status = ucfirst(strtolower($task['status'])) ?? 'To do';
    if ($status == 'Done') {
        $grouped_tasks['Done'][] = $task;
    }
}

// Calculate progress
$total_tasks = count($tasks);
$tasks_done = count($grouped_tasks['Done']);
$tasks_remaining = $total_tasks - $tasks_done;

echo json_encode([
    'total_tasks' => $total_tasks,
    'tasks_done' => $tasks_done,
    'tasks_remaining' => $tasks_remaining
]);
?>
