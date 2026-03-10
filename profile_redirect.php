<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$section = $_GET['section'] ?? '';
header('Location: profile.php#' . $section);
exit;
?>