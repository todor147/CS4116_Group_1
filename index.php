<?php
require 'includes/db_connection.php';

// Basic routing
$page = $_GET['page'] ?? 'home';

$allowed_pages = ['home', 'login', 'register', 'dashboard'];
if (!in_array($page, $allowed_pages)) {
    $page = '404';
}

require "pages/$page.php";
?> 