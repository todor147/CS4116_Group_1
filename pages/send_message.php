<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $content = trim($_POST['content']);

    if (!empty($content)) {
        try {
            // Fetch banned words
            $stmt = $pdo->prepare("SELECT word FROM BannedWords");
            $stmt->execute();
            $bannedWords = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Function to check for banned words
            function containsBannedWords($text, $bannedWords) {
                foreach ($bannedWords as $word) {
                    if (stripos($text, $word) !== false) {
                        return true;
                    }
                }
                return false;
            }

            // Check if message contains banned words
            $needs_moderation = containsBannedWords($content, $bannedWords);
            $status = $needs_moderation ? 'pending' : 'approved';

            // Insert message with appropriate status
            $stmt = $pdo->prepare("
                INSERT INTO Messages (sender_id, receiver_id, content, status, created_at, is_read)
                VALUES (?, ?, ?, ?, NOW(), 0)
            ");
            $stmt->execute([$sender_id, $receiver_id, $content, $status]);

            if ($needs_moderation) {
                $_SESSION['message'] = "Your message contains flagged content and will be reviewed by a moderator.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error sending message: " . $e->getMessage();
        }
    }
}

header("Location: conversation.php?user=" . $receiver_id);
exit();
?>
