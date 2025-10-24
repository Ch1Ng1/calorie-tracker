<?php
session_start();
$error = "";

if (!isset($_SESSION['user_id'])) {
    die("Достъп отказан.");
}
include("conf.php");

$date = $_POST['date'] ?? '';
$food = $_POST['food'] ?? '';
$calories = $_POST['calories'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
    isset($date, $food, $calories) &&
    !empty(trim($food)) &&
    strlen(trim($food)) <= 10 &&
    is_numeric($calories) &&
    strlen(trim($calories)) <= 4 &&
    $calories >= 0 &&
    $calories <= 5001 &&
    strtotime($date) <= strtotime(date("Y-m-d"))
) 
{
    
        $conn = new mysqli($h, $u, $p, $db);
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO meals (date, food, calories, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $date, $food, $calories, $user_id);
        $stmt->execute();

        header("Location: index.php");
        exit;
    } else {
        $error = "❗ Моля, попълнете всички полета коректно. Храната трябва да е до 10 букви, калориите – до 4 цифри (0–9999), а датата не може да е в бъдещето.";
    }
}
?>
