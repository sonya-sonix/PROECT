<?php
require_once 'db.php';
session_start();
if ($_SESSION['role'] !== 'admin') exit;

$id = $_POST['order_id'];
$status = $_POST['status'];

$sql = "UPDATE orders SET status = ? WHERE id = ?";
$pdo->prepare($sql)->execute([$status, $id]);

header('Location: admin.php');