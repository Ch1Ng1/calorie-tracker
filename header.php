<div style="background:#eee; padding:10px;">
  <strong>Калории Тракер</strong> |
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="index.php">Начало</a> |
    <a href="add.php">Добави</a> |
    <a href="logout.php">Изход</a>
  <?php else: ?>
    <a href="login.php">Вход</a> |
    <a href="register.php">Регистрация</a>
  <?php endif; ?>
</div>
