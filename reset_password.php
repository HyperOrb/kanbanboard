<?php
session_start();
$host = 'localhost';
$dbname = 'todo_list';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error connecting to database: " . $e->getMessage();
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: forget_password.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $user_id = $_SESSION['user_id'];
    $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);

    
    $stmt = $pdo->prepare("UPDATE login_user SET password = :password WHERE id = :id");
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':id', $user_id);

    if ($stmt->execute()) {
        $success = "Password reset successful. You can now log in.";
        session_destroy();
    } else {
        $error = "Failed to reset password. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-poppins flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-gray-800 p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold text-yellow-400 mb-6 text-center">Reset Password</h2>

        <!-- Display error or success message -->
        <?php if (isset($error)): ?>
            <div class="bg-red-500 text-white p-3 rounded-lg mb-4">
                <strong>Error:</strong> <?= htmlspecialchars($error); ?>
            </div>
        <?php elseif (isset($success)): ?>
            <div class="bg-green-500 text-white p-3 rounded-lg mb-4">
                <?= htmlspecialchars($success); ?>
            </div>
            <div class="text-center">
                <a href="index.php" class="text-yellow-400 hover:text-yellow-500 transition duration-300">Login here</a>
            </div>
        <?php else: ?>
            <form action="reset_password.php" method="POST" class="space-y-4">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-yellow-400 mb-1">New Password</label>
                    <input type="password" name="new_password" id="new_password" placeholder="********" required
                           class="w-full p-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>
                <button type="submit"
                        class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-500 transition duration-300 w-full">
                    Reset Password
                </button>
            </form>
        <?php endif; ?>
        <div class="mt-4 text-center">
            <a href="index.php" class="text-yellow-400 hover:text-yellow-500 transition duration-300">Back to Login</a>
        </div>
    </div>
</body>
</html>
