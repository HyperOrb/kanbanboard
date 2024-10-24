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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $birthdate = $_POST['birthdate']; // Ensure the name matches the input

    // Check if the email and birthdate match
    $stmt = $pdo->prepare("SELECT * FROM login_user WHERE email = :email AND birth_date = :birthdate");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':birthdate', $birthdate);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Store user info in session to reset password
        $_SESSION['user_id'] = $user['id'];
        header('Location: reset_password.php');
        exit();
    } else {
        $error = "Email or birthdate is incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forget Password</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-poppins flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-gray-800 p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold text-yellow-400 mb-6 text-center">Forget Password</h2>

        <?php if (isset($error)): ?>
            <p class="text-red-500 text-center mb-4"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="forget_password.php" method="post" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-yellow-400 mb-1">Email</label>
                <input type="email" name="email" id="email" placeholder="example@mail.com" required
                       class="w-full p-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
            </div>
            <div>
                <label for="birthdate" class="block text-sm font-medium text-yellow-400 mb-1">Birthdate</label>
                <input type="date" name="birthdate" id="birthdate" required
                       class="w-full p-3 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
            </div>
            <button type="submit"
                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-500 transition duration-300 w-full">
                Verify
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="index.php" class="text-yellow-400 hover:text-yellow-500 transition duration-300">Remember your password? Login here</a>
        </div>
    </div>
</body>
</html>
