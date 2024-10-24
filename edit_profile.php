<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$dsn = "mysql:host=localhost;dbname=todo_list";
$pdo = new PDO($dsn, "root", "");

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE login_user SET username = ?, email = ?, password = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $email, $hashed_password, $user_id]);
    } else {
        $sql = "UPDATE login_user SET username = ?, email = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $email, $user_id]);
    }

    echo json_encode(['success' => true]);
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a202c, #2d3748);
            color: white;
            font-family: 'Poppins', sans-serif;
        }
        .form-container {
            background-color: #2d3748;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            margin: 40px auto;
            max-width: 400px;
        }
        .input-field input {
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
    </style>
</head>

<body>
    <div class="form-container">
        <h2 class="text-xl font-bold mb-4">Edit Profile</h2>
        <form action="edit_profile.php" method="POST">
            <div class="input-field mb-4">
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" placeholder="New Username" required>
            </div>
            <div class="input-field mb-4">
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="New Email" required>
            </div>
            <div class="input-field mb-4">
                <input type="password" name="password" placeholder="New Password (Leave empty if not changing)">
            </div>
            <button type="submit" class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-500 transition duration-300">
                Update Profile
            </button>
            <a href="user_dashboard.php" 
                class="bg-red-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-red-500 transition duration-300">
                Back
            </a>
        </form>
    </div>
</body>

</html>