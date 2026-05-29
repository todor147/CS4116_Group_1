<?php
// Legacy URL — the coach profile now lives at coach-profile.php.
// Preserve the id and redirect so old "Back to Coach Profile" links keep working.
require_once __DIR__ . '/../includes/config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
header('Location: coach-profile.php' . ($id ? '?id=' . $id : ''), true, 301);
exit;
