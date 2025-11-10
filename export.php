<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include("conf.php");

$conn = new mysqli($h, $u, $p, $db);
$user_id = $_SESSION['user_id'];

// Взимаме данните за последния месец
$stmt = $conn->prepare("SELECT date, food, calories FROM meals 
                       WHERE user_id = ? 
                       AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)
                       ORDER BY date ASC");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Настройваме headers за CSV файл
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="calorie_tracker_export_' . date('Y-m-d') . '.csv"');

// Създаваме CSV файла
$output = fopen('php://output', 'w');

// Добавяме BOM за правилно показване на кирилица в Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Добавяме заглавния ред
fputcsv($output, array('Дата', 'Храна', 'Калории'));

// Добавяме данните
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

// Затваряме връзките
fclose($output);
$stmt->close();
$conn->close();
?>