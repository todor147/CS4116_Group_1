<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['coach_id'])) {
    echo json_encode([]);
    exit;
}

$coach_id = (int)$_GET['coach_id'];

try {
    $stmt = $pdo->prepare("
        SELECT tier_id, name, price 
        FROM ServiceTiers 
        WHERE coach_id = ?
    ");
    $stmt->execute([$coach_id]);
    $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($tiers);
} catch (PDOException $e) {
    error_log('Error fetching tiers: ' . $e->getMessage());
    echo json_encode([]);
} 