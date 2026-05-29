<?php
/**
 * EduCoach — front entry point.
 *
 * Verifies the app can boot (config + database) and forwards visitors to the
 * home page. All real pages live under /pages.
 */

require_once __DIR__ . '/includes/db_connection.php';

header('Location: ' . BASE_PATH . '/pages/home.php');
exit;
