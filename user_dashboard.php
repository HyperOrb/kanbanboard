<?php
session_start();

// Redirect to login if the user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$dsn = "mysql:host=localhost;dbname=todo_list";
$pdo = new PDO($dsn, "root", "");

// Handle task deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task_id'])) {
    $task_id = $_POST['delete_task_id'];
    $user_id = $_SESSION['user_id'];

    // Delete task from the database
    $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$task_id, $user_id]);

    header('Location: user_dashboard.php');
    exit;
}

// Fetch tasks for the logged-in user and group them by status
$sql = "SELECT * FROM tasks WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Ensure consistent capitalization for the status groups
$grouped_tasks = [
    'To do' => [],
    'In progress' => [],
    'On check' => [],
    'Done' => []
];

// Group tasks by status
foreach ($tasks as $task) {
    // Ensure case consistency when grouping tasks by status
    $status = ucfirst(strtolower($task['status'])) ?? 'To do';  // Default to 'To do' if status is null or invalid
    if (isset($grouped_tasks[$status])) {
        $grouped_tasks[$status][] = $task;
    } else {
        $grouped_tasks['To do'][] = $task;  // Fallback to 'To do' if status is not valid
    }
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT username, email FROM login_user WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user data is fetched properly
if (!$user) {
    // If no user found, redirect to login or show an error
    header('Location: index.php');
    exit;
}

// Calculate progress overview
$total_tasks = count($tasks);
$tasks_done = count($grouped_tasks['Done']);
$tasks_remaining = $total_tasks - $tasks_done;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Kanban Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a202c, #2d3748);
            color: white;
            font-family: 'Poppins', sans-serif;
        }

        .kanban-board {
    display: flex;
    gap: 16px;
    flex-wrap: wrap; /* Ensure columns wrap to a new line on smaller screens */
    overflow-x: auto;
    padding: 20px;
    margin-top: 30px;
}

.kanban-column {
    width: 100%;
    max-width: 350px;  /* Set a max width for better adaptability */
    flex: 1 1 100%;    /* Allow columns to grow and shrink */
    background-color: #2d3748;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    margin-bottom: 20px;  /* Add margin to prevent overlap when wrapped */
}


        .kanban-column h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 20px;
            border-bottom: 2px solid #4a5568;
        }

        .task-card {
            background-color: #4a5568;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            cursor: grab;
        }

        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .task-card img {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 12px;
        }

        .form-container {
            background-color: #2d3748;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            margin-bottom: 40px;
        }

        .form-container h2 {
            color: #f7fafc;
            font-size: 1.5rem;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .input-field input,
        .input-field textarea,
        .input-field select {
            background-color: #4a5568;
            color: #f7fafc;
            border: none;
            border-radius: 8px;
            padding: 10px;
            width: 100%;
            margin-top: 8px;
        }

        .input-field input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .kanban-column.over {
            border: 2px dashed #ecc94b;
        }

        .empty-message {
            color: #9ca3af;
        }

        .button-container {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }

        .edit-button,
        .delete-button {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .edit-button {
            background-color: #38b2ac;
            color: white;
        }

        .edit-button:hover {
            background-color: #319795;
        }

        .delete-button {
            background-color: #e53e3e;
            color: white;
        }

        .delete-button:hover {
            background-color: #c53030;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.3s;
        }

        .modal.active {
            visibility: visible;
            opacity: 1;
        }

        .modal-content {
            background-color: #2d3748;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 400px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-button {
            background: transparent;
            border: none;
            color: #e53e3e;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background-color: #38b2ac;
            /* Teal color for success */
            color: #ffffff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            opacity: 1;
            animation: fadeout 5s forwards;
        }

        @keyframes fadeout {
            0% {
                opacity: 1;
            }

            80% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        /* Navbar Styles for Left-side Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            background-color: #1a202c;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.5);
        }

        .navbar-logo {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
        }

        .navbar-logo img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }

        .navbar-links {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .navbar-links a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #f7fafc;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .navbar-links a:hover {
            color: #ecc94b;
        }

        .navbar-links img {
            width: 24px;
            height: 24px;
        }

        .user-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 40px;
        }

        .user-profile-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .user-name {
            font-weight: bold;
            color: #f7fafc;
        }

        .user-status {
            color: #38b2ac;
            font-size: 0.9rem;
        }

        .progress-overview {
            margin-top: 30px;
            padding: 20px;
            background-color: #2d3748;
            border-radius: 10px;
            text-align: center;
        }

        .progress-title {
            color: #ecc94b;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .progress-item {
            color: #f7fafc;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <!-- Navbar on the Left Side -->
    <nav class="navbar">
        <div class="navbar-logo">
            <img src="https://img.icons8.com/color/48/null/task.png" alt="Kanban Logo">
            <h1 class="text-2xl font-bold text-yellow-400">Kanban Dashboard</h1>
        </div>
        <div class="user-profile">
            <img src="https://img.icons8.com/ios-filled/50/38b2ac/user.png" alt="User Icon" class="user-profile-icon">
            <p class="user-name">Hello, <?= htmlspecialchars($user['username']) ?></p>
            <p class="user-status">Status: Online</p>
        </div>

        <div class="navbar-links">
            <a href="#" onclick="openAddTaskModal()">
                <img src="https://img.icons8.com/ios-filled/50/38b2ac/add.png" alt="Add Icon">
                Add New Task
            </a>
            <a href="#" onclick="openEditProfileModal()">
                <img src="https://img.icons8.com/ios-filled/50/38b2ac/edit-user-male.png" alt="Edit Profile Icon">
                Edit Profile
            </a>
            <a href="logout.php">
                <img src="https://img.icons8.com/ios-filled/50/38b2ac/exit.png" alt="Logout Icon">
                Logout
            </a>
            <div class="progress-overview">
                <h3 class="progress-title">Progress Overview</h3>
                <p class="progress-item total-tasks">Total Tasks: <?= $total_tasks ?></p>
                <p class="progress-item tasks-done">Tasks Completed: <?= $tasks_done ?></p>
                <p class="progress-item tasks-remaining">Tasks Remaining: <?= $tasks_remaining ?></p>
            </div>

        </div>
    </nav>


    <div class="container mx-auto mt-12" style="margin-left: 270px;">

        <!-- Display Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-500 text-white p-4 rounded mb-4">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-500 text-white p-4 rounded mb-4">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Kanban Board -->
        <div class="kanban-board">
            <?php foreach ($grouped_tasks as $status => $tasks): ?>
                <div class="kanban-column" data-status="<?= htmlspecialchars($status) ?>" ondrop="drop(event)"
                    ondragover="allowDrop(event)">
                    <h2><?= htmlspecialchars($status) ?></h2>
                    <div class="task-list">
                        <?php if (!empty($tasks)): ?>
                            <?php foreach ($tasks as $task): ?>
                                <div class="task-card" draggable="true" ondragstart="drag(event)" data-task-id="<?= $task['id'] ?>">
                                    <h3 class="font-bold text-xl text-yellow-300" id="task-title-<?= $task['id'] ?>">
                                        <?= htmlspecialchars($task['title']) ?>
                                    </h3>
                                    <p class="text-gray-300" id="task-description-<?= $task['id'] ?>">
                                        <?= htmlspecialchars($task['description']) ?>
                                    </p>
                                    <p class="text-sm text-yellow-400" id="task-due-date-<?= $task['id'] ?>">Due:
                                        <?= htmlspecialchars($task['due_date']) ?>
                                    </p>
                                    <p class="text-sm text-gray-400" id="task-participants-<?= $task['id'] ?>">Participants:
                                        <?= htmlspecialchars($task['participants']) ?>
                                    </p>
                                    <?php if ($task['image_path']): ?>
                                        <img src="<?= htmlspecialchars($task['image_path']) ?>" alt="Task Image"
                                            id="task-image-<?= $task['id'] ?>">
                                    <?php endif; ?>
                                    <div class="button-container">
                                    <button class="edit-button"
    onclick="handleEditClick(<?= htmlspecialchars(json_encode($task)) ?>)">Edit</button>

                                        <form action="user_dashboard.php" method="POST"
                                            onsubmit="deleteTask(<?= $task['id'] ?>); return false;">
                                            <input type="hidden" name="delete_task_id" value="<?= $task['id'] ?>">
                                            <button type="submit" class="delete-button">Delete</button>
                                        </form>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-message">No tasks here</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Task</h2>
                <button class="close-button" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editTaskForm" onsubmit="saveTaskChanges(event)" enctype="multipart/form-data">
                <input type="hidden" name="task_id" id="editTaskId">
                <div class="input-field mb-4">
                    <input type="text" name="title" id="editTitle" placeholder="Task Title" required>
                </div>
                <div class="input-field mb-4">
                    <textarea name="description" id="editDescription" rows="3"
                        placeholder="Task Description"></textarea>
                </div>
                <div class="input-field mb-4">
                    <select name="status" id="editStatus" required>
                        <option value="To do">To do</option>
                        <option value="In progress">In progress</option>
                        <option value="On check">On check</option>
                        <option value="Done">Done</option>
                    </select>
                </div>
                <div class="input-field mb-4">
                    <input type="date" name="due_date" id="editDueDate" required>
                </div>
                <div class="input-field mb-4">
                    <input type="text" name="participants" id="editParticipants"
                        placeholder="Participants (comma separated)">
                </div>
                <button type="submit"
                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-500 transition duration-300">
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div id="addTaskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Task</h2>
                <button class="close-button" onclick="closeAddTaskModal()">&times;</button>
            </div>
            <form id="addTaskForm" onsubmit="addNewTask(event)" enctype="multipart/form-data">
                <div class="input-field mb-4">
                    <input type="text" name="title" id="addTitle" placeholder="Task Title" required>
                </div>
                <div class="input-field mb-4">
                    <textarea name="description" id="addDescription" rows="3" placeholder="Task Description"></textarea>
                </div>
                <div class="input-field mb-4">
                    <select name="status" id="addStatus" required>
                        <option value="To do">To do</option>
                        <option value="In progress">In progress</option>
                        <option value="On check">On check</option>
                        <option value="Done">Done</option>
                    </select>
                </div>
                <div class="input-field mb-4">
                    <input type="date" name="due_date" id="addDueDate" required>
                </div>
                <div class="input-field mb-4">
                    <input type="text" name="participants" id="addParticipants"
                        placeholder="Participants (comma separated)">
                </div>
                <div class="input-field mb-4">
                    <input type="file" name="task_image" id="addTaskImage">
                </div>
                <button type="submit"
                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-500 transition duration-300">
                    Add Task
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="close-button" onclick="closeEditProfileModal()">&times;</button>
            </div>
            <form id="editProfileForm" onsubmit="updateProfile(event)">
                <div class="input-field mb-4">
                    <input type="text" name="username" id="profileUsername" placeholder="New Username" required>
                </div>
                <div class="input-field mb-4">
                    <input type="email" name="email" id="profileEmail" placeholder="New Email" required>
                </div>
                <div class="input-field mb-4">
                    <input type="password" name="password" id="profilePassword"
                        placeholder="New Password (Leave empty if not changing)">
                </div>
                <button type="submit"
                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-500 transition duration-300">
                    Update Profile
                </button>
            </form>
        </div>
    </div>


    <script>
        let draggedTask = null;

        function allowDrop(event) {
            event.preventDefault();
        }

        function drag(event) {
            draggedTask = event.target.closest('.task-card'); // Select the entire card for dragging
        }

        function drop(event) {
            event.preventDefault();
            const column = event.target.closest('.kanban-column');
            if (column && draggedTask) {
                const newStatus = column.getAttribute('data-status');
                const taskId = draggedTask.getAttribute('data-task-id');

                // Move the dragged task to the new column
                const taskList = column.querySelector('.task-list');
                taskList.appendChild(draggedTask);

                // Check and update empty messages for all columns
                updateEmptyMessages();

                // Send an AJAX request to update task status in the database
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'update_task_status.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                        showToast('Task status updated successfully');
                        updateProgress(); // Update progress instantly
                    }
                };
                xhr.send(`task_id=${taskId}&status=${newStatus}`);
            }
        }


        function handleEditClick(task) {
    // Store task data in localStorage
    localStorage.setItem('editTask', JSON.stringify(task));
    
    // Store a flag to indicate the page needs to reload and open the modal
    localStorage.setItem('reloadForEdit', 'true');

    // Reload the page
    window.location.reload();
}

function handleEditClick(task) {
    // Store task data in localStorage
    localStorage.setItem('editTask', JSON.stringify(task));
    
    // Store a flag to indicate the page needs to reload and open the modal
    localStorage.setItem('reloadForEdit', 'true');

    // Reload the page
    window.location.reload();
}

// Function to add event listeners to all edit buttons
function initializeEditButtons() {
    const editButtons = document.querySelectorAll('.edit-button');
    
    // Loop through each button and add the click event listener
    editButtons.forEach(button => {
        button.removeEventListener('click', editButtonHandler); // Remove old listeners to avoid duplicates
        button.addEventListener('click', editButtonHandler); // Add fresh listener
    });
}

// Click handler for the edit buttons
function editButtonHandler(event) {
    const task = JSON.parse(this.dataset.task);  // Retrieve task data from data attribute
    handleEditClick(task);  // Trigger the modal open with the task data
}

// Initialize edit buttons when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeEditButtons();  // Call this on page load

    // Check if the page needs to reload for edit
    const reloadForEdit = localStorage.getItem('reloadForEdit');
    if (reloadForEdit === 'true') {
        // Remove the flag so it doesn't repeat on subsequent page loads
        localStorage.removeItem('reloadForEdit');

        // Get the task data from localStorage
        const storedTask = localStorage.getItem('editTask');
        if (storedTask) {
            const task = JSON.parse(storedTask);

            // Open the edit modal with the task details
            openEditModal(task);

            // Clear the stored task data after it's used
            localStorage.removeItem('editTask');
        }
    }
});



        function updateProgress() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_progress.php', true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        console.error(response.error);
                        return;
                    }

                    document.querySelector('.progress-item.total-tasks').innerText = `Total Tasks: ${response.total_tasks}`;
                    document.querySelector('.progress-item.tasks-done').innerText = `Tasks Completed: ${response.tasks_done}`;
                    document.querySelector('.progress-item.tasks-remaining').innerText = `Tasks Remaining: ${response.tasks_remaining}`;
                }
            };
            xhr.send();
        }



        function openEditModal(task) {
            document.getElementById('editTaskId').value = task.id;
            document.getElementById('editTitle').value = task.title;
            document.getElementById('editDescription').value = task.description;
            document.getElementById('editStatus').value = task.status;
            document.getElementById('editDueDate').value = task.due_date;
            document.getElementById('editParticipants').value = task.participants;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function saveTaskChanges(event) {
            event.preventDefault();

            const taskId = document.getElementById('editTaskId').value;
            const title = document.getElementById('editTitle').value;
            const description = document.getElementById('editDescription').value;
            const status = document.getElementById('editStatus').value;
            const dueDate = document.getElementById('editDueDate').value;
            const participants = document.getElementById('editParticipants').value;

            // Update the UI directly without reloading
            document.getElementById(`task-title-${taskId}`).innerText = title;
            document.getElementById(`task-description-${taskId}`).innerText = description;
            document.getElementById(`task-due-date-${taskId}`).innerText = `Due: ${dueDate}`;
            document.getElementById(`task-participants-${taskId}`).innerText = `Participants: ${participants}`;

            // Send an AJAX request to update the task in the database
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_task.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                    showToast('Task updated successfully');
                    closeEditModal();
                    updateProgress(); // Update progress instantly
                }
            };
            xhr.send(`task_id=${taskId}&title=${title}&description=${description}&status=${status}&due_date=${dueDate}&participants=${participants}`);
        }




        // Function to update "No tasks here" messages for all columns
        function updateEmptyMessages() {
            const columns = document.querySelectorAll('.kanban-column');

            columns.forEach(column => {
                const taskList = column.querySelector('.task-list');
                const tasks = taskList.querySelectorAll('.task-card');
                let emptyMessage = taskList.querySelector('.empty-message');

                // If there's no empty message element, create one
                if (!emptyMessage) {
                    emptyMessage = document.createElement('p');
                    emptyMessage.className = 'empty-message';
                    emptyMessage.textContent = 'No tasks here';
                    taskList.appendChild(emptyMessage);
                }

                // Toggle visibility based on the presence of tasks
                if (tasks.length === 0) {
                    emptyMessage.style.display = 'block';
                } else {
                    emptyMessage.style.display = 'none';
                }
            });
        }
        document.addEventListener('DOMContentLoaded', updateEmptyMessages);

        function openAddTaskModal() {
            document.getElementById('addTaskModal').classList.add('active');
        }

        function closeAddTaskModal() {
    document.getElementById('addTaskModal').classList.remove('active');
}

function addNewTask(event) {
    event.preventDefault(); // Prevent the default form submission

    const formData = new FormData(document.getElementById('addTaskForm'));

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'add_new_task.php', true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);

                if (response.error) {
                    console.error(response.error);
                    showToast('Error adding task: ' + response.error); // Show error message in toast
                    return;
                }

                showToast('Task added successfully'); // Show success message

                // Close modal after successful addition
                closeAddTaskModal();

                // Refresh the page to reflect the new task
                location.reload(); // This line refreshes the page
            } else {
                console.error('Failed to add task: ' + xhr.statusText);
                showToast('Failed to add task: ' + xhr.statusText); // Show toast for error response
            }
        }
    };
    xhr.send(formData); // Send the form data
}





        function showToast(message) {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.classList.add('toast');
            toast.innerText = message;

            // Append the toast message to the container
            toastContainer.appendChild(toast);

            // Remove the toast after 5 seconds
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        function deleteTask(taskId) {
            if (!confirm('Are you sure you want to delete this task?')) {
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'delete_task.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            // Handle the response
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // Remove the task from the DOM
                                const taskElement = document.querySelector(`[data-task-id="${taskId}"]`);
                                if (taskElement) {
                                    taskElement.parentNode.removeChild(taskElement);
                                }

                                // Update empty messages and progress instantly
                                updateEmptyMessages();
                                updateProgress();

                                // Show success notification
                                showToast('Task deleted successfully!');
                            } else {
                                showToast('Failed to delete task!', 'error');
                                console.error(response.error);
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            showToast('Failed to delete task!', 'error');
                        }
                    } else {
                        showToast('Failed to delete task! Please try again.', 'error');
                    }
                }
            };

            // Send the AJAX request
            xhr.send(`task_id=${taskId}`);
        }

        function openEditProfileModal() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_profile_data.php', true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const user = JSON.parse(xhr.responseText);
                    document.getElementById('profileUsername').value = user.username;
                    document.getElementById('profileEmail').value = user.email;
                }
            };
            xhr.send();

            document.getElementById('editProfileModal').classList.add('active');
        }

        function closeEditProfileModal() {
            document.getElementById('editProfileModal').classList.remove('active');
        }

        function updateProfile(event) {
    event.preventDefault();

    const formData = new FormData(document.getElementById('editProfileForm'));

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'edit_profile.php', true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showToast('Profile updated successfully!');

                // Close the modal
                closeEditProfileModal();

                // Refresh the page after successful update
                window.location.reload();
            } else {
                alert('An error occurred: ' + response.error);
            }
        }
    };
    xhr.send(formData);
}

    </script>
    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

</body>

</html>