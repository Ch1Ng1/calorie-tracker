<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include("security_headers.php");
include("header.php");
include("csrf.php");

// Показване на грешка ако съществува
$error = null;
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
}
if (isset($_SESSION['insert_error'])) {
    $error = htmlspecialchars($_SESSION['insert_error'], ENT_QUOTES, 'UTF-8');
    unset($_SESSION['insert_error']);
}
?>

<h2>Добави храна</h2>
<?php if ($error): ?>
    <div style="color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
        <?= $error ?>
    </div>
<?php endif; ?>
<form action="insert.php" method="post">
  <?php echo getCsrfField(); ?>
  <input type="date" name="date" max="<?= date('Y-m-d') ?>" required><br>
  <input type="text" name="food" maxlength="50" pattern="[A-Za-z0-9А-Яа-я\s\-]+" required placeholder="Храна" title="Само букви, цифри, пространство и хифен"><br>
  <input type="number" name="calories" required min="5" max="5000" placeholder="Калории" aria-label="Калории"><br>
  <input type="submit" value="Запиши">
</form>
