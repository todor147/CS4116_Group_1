<?php
session_start();
require 'includes/db_connection.php';

// Basic routing
$page = $_GET['page'] ?? 'home';

$allowed_pages = ['home', 'login', 'register', 'dashboard', 'review', 'sessions'];
if (!in_array($page, $allowed_pages)) {
    $page = '404';
}

// Redirect to login if not authenticated
if (!isset($_SESSION['logged_in']) && $page !== 'login' && $page !== 'register') {
    header('Location: login.php');
    exit;
}

require "pages/$page.php";
?> 
