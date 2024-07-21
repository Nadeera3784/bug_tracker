<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/db.php';

session_start();


if (isAuthenticated()) {
    header('Location: admin/dashboard.php');
    exit();
}

// Check if we're receiving a callback from GitHub
if (isset($_GET['code'])) {
    try {
        authenticateUser($_GET['code']);
        header('Location: admin/dashboard.php');
        exit();
    } catch (Exception $e) {
        $error = "Authentication failed: " . $e->getMessage();
    }
}

// Generate a random state parameter to prevent CSRF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Construct the GitHub OAuth URL
$github_oauth_url = getAuthURL($state);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Tracker - Login</title>
    <?php include '../includes/header.php'; ?>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <h1 class="text-3xl font-bold mb-4 text-center text-gray-800">Bug Tracker</h1>
            <p class="mb-6 text-center text-gray-600">Welcome to the Bug Tracker. Please log in to continue.</p>
            
            <?php if (isset($error)): ?>
            <p class="text-red-500 mb-4 text-center"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <a href="<?= htmlspecialchars($github_oauth_url) ?>" class="block w-full bg-gray-800 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out text-center">
                Login with GitHub
            </a>
        </div>
    </div>
</body>
</html>
