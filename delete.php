<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Достъп отказан.");
}
include("conf.php");

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $conn = new mysqli($h, $u, $p, $db);
    $stmt = $conn->prepare("DELETE FROM meals WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
}
header("Location: index.php");
?>
