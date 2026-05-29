<?php
// Legacy/orphaned page that targeted a different schema (users/id/first_name…)
// and never worked with this database. The live "request customer insights"
// feature is customer-insight-request.php (linked from coach profiles).
// Redirect there, preserving the coach id.
require_once __DIR__ . '/../includes/config.php';

$coach_id = isset($_GET['coach_id']) ? (int) $_GET['coach_id'] : 0;
header('Location: customer-insight-request.php' . ($coach_id ? '?coach_id=' . $coach_id : ''), true, 301);
exit;
