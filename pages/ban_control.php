<?php
session_start();

require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];

    // Fetch the current banned status
    $stmt = $pdo->prepare("SELECT is_banned FROM Users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Toggle the banned status
        $new_status = $user['is_banned'] ? 0 : 1;

        // Update the user's banned status
        $stmt = $pdo->prepare("UPDATE Users SET is_banned = ? WHERE user_id = ?");
        $stmt->execute([$new_status, $user_id]);
    }
}

// Redirect back to the manage users page
header("Location: manage-users.php");
exit();
?>