<?php
require_once 'db.php';
$term = $_GET['term'] ?? '';

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

// Убираем знак % перед переменной для поиска именно с начала
$stmt = $pdo->prepare("SELECT street_name FROM grodno_streets WHERE street_name LIKE ? ORDER BY street_name ASC LIMIT 10");
$stmt->execute([$term . '%']); 
$streets = $stmt->fetchAll(PDO::FETCH_COLUMN);

header('Content-Type: application/json');
echo json_encode($streets);