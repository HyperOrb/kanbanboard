<?php
session_start();

// Redirect to login if the user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$dsn = "mysql:host=localhost;dbname=todo_list";
$pdo = new PDO($dsn, "root", "");

// Handle form submissions: Add new task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];
    $participants = $_POST['participants'];
    $user_id = $_SESSION['user_id'];

    // Handle image upload if provided
    $image_path = null;
    if (!empty($_FILES['task_image']['name'])) {
        $target_dir = "uploads/";
        $image_name = uniqid() . '-' . basename($_FILES["task_image"]["name"]);
        $image_path = $target_dir . $image_name;
        move_uploaded_file($_FILES["task_image"]["tmp_name"], $image_path);
    }

    // Insert new task into the database
    $sql = "INSERT INTO tasks (user_id, title, description, status, due_date, participants, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $title, $description, $status, $due_date, $participants, $image_path]);

    header('Location: kanban_dashboard.php');
    exit;
}

// Fetch tasks for the logged-in user and group them by status
$sql = "SELECT * FROM tasks WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_tasks = [
    'To do' => [],
    'In progress' => [],
    'On check' => [],
    'Done' => []
];

foreach ($tasks as $task) {
    $grouped_tasks[$task['status']][] = $task;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4c4c6d, #1f2a48);
            color: white;
        }
        .kanban-board {
            display: flex;
            gap: 20px;
            overflow-x: auto;
        }
        .kanban-column {
            width: 300px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 16px;
        }
        .kanban-column h2 {
            color: #ffdd57;
        }
        .task-card {
            background-color: #2d3748;
            margin-bottom: 12px;
            padding: 12px;
            border-radius: 8px;
        }
        .task-card img {
            max-width: 100%;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <nav class="bg-gray-800 p-4">
        <div class="container mx-auto flex justify-between">
            <h1 class="text-xl">Kanban Dashboard</h1>
            <a href="logout.php" class="hover:underline">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8">
        <h2 class="text-2xl mb-4">Add New Task</h2>
        <form action="kanban_dashboard.php" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium">Title</label>
                <input type="text" name="title" required class="mt-1 p-2 border rounded w-full">
            </div>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium">Description</label>
                <textarea name="description" rows="3" class="mt-1 p-2 border rounded w-full"></textarea>
            </div>
            <div class="mb-4">
                <label for="status" class="block text-sm font-medium">Status</label>
                <select name="status" class="mt-1 p-2 border rounded w-full">
                    <option value="To do">To do</option>
                    <option value="In progress">In progress</option>
                    <option value="On check">On check</option>
                    <option value="Done">Done</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="due_date" class="block text-sm font-medium">Due Date</label>
                <input type="date" name="due_date" class="mt-1 p-2 border rounded w-full">
            </div>
            <div class="mb-4">
                <label for="participants" class="block text-sm font-medium">Participants</label>
                <input type="text" name="participants" placeholder="John, Doe" class="mt-1 p-2 border rounded w-full">
            </div>
            <div class="mb-4">
                <label for="task_image" class="block text-sm font-medium">Upload Image</label>
                <input type="file" name="task_image" class="mt-1">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Add Task</button>
        </form>

        <div class="kanban-board mt-8">
            <?php foreach ($grouped_tasks as $status => $tasks): ?>
                <div class="kanban-column">
                    <h2><?= htmlspecialchars($status) ?></h2>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-card">
                            <h3><?= htmlspecialchars($task['title']) ?></h3>
                            <p><?= htmlspecialchars($task['description']) ?></p>
                            <p>Due: <?= htmlspecialchars($task['due_date']) ?></p>
                            <p>Participants: <?= htmlspecialchars($task['participants']) ?></p>
                            <?php if ($task['image_path']): ?>
                                <img src="<?= htmlspecialchars($task['image_path']) ?>" alt="Task Image">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
