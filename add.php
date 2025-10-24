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
  <input type="text" name="food" maxlength="10" required placeholder="Храна"><br>
  <input type="number" maxlength="4" name="calories" required min="5" max="5000" required placeholder="Калории"><br>
  <input type="submit" value="Запиши">
</form>
