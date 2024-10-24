<?php
session_start();

$dsn = "mysql:host=localhost;dbname=todo_list";
$pdo = new PDO($dsn, "root", "");

// Initialize an error message variable
$error_msg = "";

// Check if the request method is POST (i.e., form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $birth_date = $_POST['birth_date'];  // Capture birth_date

    // Validate if password and confirmation match
    if ($password !== $confirm_password) {
        $error_msg = "Password and password confirmation do not match.";
    } else {
        // Check if the username or email already exists in the database
        $sql = "SELECT * FROM login_user WHERE username = ? OR email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $error_msg = "Username or email is already taken. Please choose another.";
        } else {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert the new user into the database with birth_date
            $sql = "INSERT INTO login_user (username, email, password, birth_date) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$username, $email, $hashed_password, $birth_date])) {
                echo "Registration successful!";
                // Redirect to login page after successful registration
                header('Location: index.php');
                exit;
            } else {
                $error_msg = "An error occurred while trying to register.";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Register Page</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-poppins flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-gray-800 p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold text-yellow-400 mb-6 text-center">Register</h2>

        <!-- Display error message if any -->
        <?php if (!empty($error_msg)): ?>
        <div class="bg-red-500 text-white p-3 rounded-lg mb-4">
            <strong>Error:</strong> <?= htmlspecialchars($error_msg); ?>
        </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-yellow-400 mb-1">Username</label>
                <input type="text" name="username" placeholder="John Thor" required
                       class="w-full p-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-yellow-400 mb-1">Email</label>
                <input type="email" name="email" placeholder="example@mail.com" required
                       class="w-full p-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
            </div>
            <div>
    <label for="birth_date" class="block text-sm font-medium text-yellow-400 mb-1">Birth Date</label>
    <input type="date" name="birth_date" placeholder="MM/DD/YYYY" required
           class="w-full p-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
</div>

            <div>
                <label for="password" class="block text-sm font-medium text-yellow-400 mb-1">Password</label>
                <input type="password" name="password" placeholder="********" required
                       class="w-full p-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-yellow-400 mb-1">Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="********" required
                       class="w-full p-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
            </div>
            <button type="submit"
                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-500 transition duration-300 w-full">
                Register
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="index.php" class="text-yellow-400 hover:text-yellow-500 transition duration-300">Have an account? Login here.</a>
        </div>
    </div>
</body>
</html>
