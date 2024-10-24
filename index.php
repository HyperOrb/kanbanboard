<?php
session_start();

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: user_dashboard.php'); // Redirect if already logged in
    exit; 
}

$dsn = "mysql:host=localhost;dbname=todo_list";
$pdo = new PDO($dsn, "root", "");

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the email exists in the database
    $sql = "SELECT * FROM login_user WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password only if the user exists
    if ($user && password_verify($password, $user['password'])) {
        // Password is correct, store user details in session and redirect
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: user_dashboard.php'); // Redirect to dashboard
        exit;
    } else {
        // If login fails, display an error message
        $error_message = "Wrong email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Login Page</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-poppins flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-gray-800 p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold text-yellow-400 mb-6 text-center">Login</h2>
        <form action="index.php" method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-yellow-400 mb-1">Email</label>
                <input type="email" id="email" name="email" placeholder="example@mail.com" required
                       class="w-full p-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-yellow-400 mb-1">Password</label>
                <input type="password" id="password" name="password" placeholder="********" required
                       class="w-full p-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
            </div>
            <button type="submit"
                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-500 transition duration-300 w-full">
                Login
            </button>
        </form>
        <!-- Display error message if login fails -->
        <?php if (isset($error_message)): ?>
            <p class="text-red-500 mt-4 text-center"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <div class="mt-4 text-center">
            <a href="register.php" class="text-yellow-400 hover:text-yellow-500 transition duration-300">Don't have an account? Register here.</a>
        </div>
        <div class="mt-4 text-center">
            <a href="forget_password.php" class="text-yellow-400 hover:text-yellow-500 transition duration-300">Forget Password?</a>
        </div>
    </div>
</body>
</html>
