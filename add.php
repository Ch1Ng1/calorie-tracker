<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include("header.php");
?>

<h2>Добави храна</h2>
<form action="insert.php" method="post">
  <input type="date" name="date" max="<?= date('Y-m-d') ?>" required><br>
  <input type="text" name="food" maxlength="50" pattern="[A-Za-zА-Яа-я\s]+" required placeholder="Храна"><br>
  <input type="number" name="calories" required min="5" max="5000" placeholder="Калории" aria-label="Калории"><br>
  <input type="submit" value="Запиши">
</form>
