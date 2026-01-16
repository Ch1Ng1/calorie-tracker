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
    strlen(trim($food)) <= 50 &&
    preg_match('/^[A-Za-zА-Яа-я\s]+$/u', trim($food)) &&
    is_numeric($calories) &&
    $calories >= 0 &&
    $calories <= 5000 &&
    strtotime($date) <= strtotime(date("Y-m-d"))
) 
{
    
        $conn = new mysqli($h, $u, $p, $db);
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO meals (date, food, calories, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $date, $food, $calories, $user_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        header("Location: index.php");
        exit;
    } else {
        $error = "❌ Моля, попълнете всички полета коректно. Храната трябва да е до 50 букви (само букви), калориите – между 0-5000, а датата не може да е в бъдещето.";
    }
}
?>
