<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = $_POST['message_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'approve' || $action === 'reject') {
            $stmt = $pdo->prepare("UPDATE Messages SET status = ? WHERE message_id = ?");
            $stmt->execute([$action === 'approve' ? 'approved' : 'rejected', $message_id]);
            
            // Set success message
            $_SESSION['success'] = "Message has been " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error moderating message: " . $e->getMessage();
    }
}

header('Location: message-moderation.php');
exit();
