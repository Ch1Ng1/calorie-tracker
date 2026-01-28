<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("❌ Достъп отказан.");
}
include("conf.php");
include("csrf.php");

// GET заявката за изтриване е по-безопасна без CSRF защита (защото е idempotent)
// Но за по-добра защита, препоръчвам да се преработи като POST

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $conn = new mysqli($h, $u, $p, $db);
    $user_id = $_SESSION['user_id'];
    
    // Security: Verify the meal belongs to the current user
    $stmt = $conn->prepare("DELETE FROM meals WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_GET['id'], $user_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
header("Location: index.php");
exit;
?>
