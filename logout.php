<?php
include("session_config.php");
session_destroy();

// Пренасочване към login или начална страница
header("Location: login.php"); // или "index.php" ако искаш директно към дневника
exit;
?>
