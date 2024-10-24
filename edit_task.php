<?php
session_start();

// Redirect to login if the user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$dsn = "mysql:host=localhost;dbname=todo_list";
$pdo = new PDO($dsn, "root", "");

// Fetch the task to edit
if (!isset($_GET['task_id'])) {
    header('Location: user_dashboard.php');
    exit;
}

$task_id = $_GET['task_id'];
$user_id = $_SESSION['user_id'];

// Get task details
$sql = "SELECT * FROM tasks WHERE id = ? AND user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$task_id, $user_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header('Location: user_dashboard.php');
    exit;
}

// Handle form submission for updating the task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $status = $_POST['status'] ?? 'To do';  // Fallback to 'To do' if no status is provided
    $due_date = $_POST['due_date'];
    $participants = $_POST['participants'];

    // Handle image upload if provided
    $image_path = $task['image_path'];
    if (!empty($_FILES['task_image']['name'])) {
        $target_dir = "uploads/";
        $image_name = uniqid() . '-' . basename($_FILES["task_image"]["name"]);
        $image_path = $target_dir . $image_name;
        move_uploaded_file($_FILES["task_image"]["tmp_name"], $image_path);
    }

    // Update task in the database
    $sql = "UPDATE tasks SET title = ?, description = ?, status = ?, due_date = ?, participants = ?, image_path = ? WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $description, $status, $due_date, $participants, $image_path, $task_id, $user_id]);

    header('Location: user_dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mx-auto mt-8">
        <div class="form-container">
            <h2>Edit Task</h2>
            <form action="edit_task.php?task_id=<?= $task['id'] ?>" method="POST" enctype="multipart/form-data">
                <div class="input-field">
                    <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required>
                </div>
                <div class="input-field">
                    <textarea name="description" rows="3" required><?= htmlspecialchars($task['description']) ?></textarea>
                </div>
                <div class="input-field">
                    <select name="status" required>
                        <option value="To do" <?= $task['status'] == 'To do' ? 'selected' : '' ?>>To do</option>
                        <option value="In progress" <?= $task['status'] == 'In progress' ? 'selected' : '' ?>>In progress</option>
                        <option value="On check" <?= $task['status'] == 'On check' ? 'selected' : '' ?>>On check</option>
                        <option value="Done" <?= $task['status'] == 'Done' ? 'selected' : '' ?>>Done</option>
                    </select>
                </div>
                <div class="input-field">
                    <input type="date" name="due_date" value="<?= htmlspecialchars($task['due_date']) ?>" required>
                </div>
                <div class="input-field">
                    <input type="text" name="participants" value="<?= htmlspecialchars($task['participants']) ?>">
                </div>
                <div class="input-field">
                    <input type="file" name="task_image">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Update Task</button>
            </form>
        </div>
    </div>
</body>
</html>
