<?php
require_once 'db.php';
session_start();
if ($_SESSION['role'] !== 'admin') exit;

$id = $_POST['id'];
$name = $_POST['name'];
$price = $_POST['price'];
$avail = $_POST['is_available'];

$sql = "UPDATE products SET name = ?, base_price = ?, is_available = ? WHERE id = ?";
$pdo->prepare($sql)->execute([$name, $price, $avail, $id]);

header('Location: admin.php'); // Возвращаемся обратно