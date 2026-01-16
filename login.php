<?php
session_start();
include("conf.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $conn = new mysqli($h, $u, $p, $db);

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed);
        $stmt->fetch();

        if (password_verify($password, $hashed)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit;
        } else {
            $error = "Грешна парола.";
        }
    } else {
        $error = "Потребителят не съществува.";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <title>Вход</title>
</head>
<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background: #f0f4f8;
    color: #333;
    text-align: center;
    padding: 50px;
  }
  input[type="text"], input[type="password"] {
    padding: 10px;
    margin: 10px 0;
    width: 200px;
    border: 1px solid #ccc;
    border-radius: 5px;
  }
  input[type="submit"] {
    padding: 10px 20px;
    background: #00796b;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  input[type="submit"]:hover {
    background: #004d40;
  }
  a {
    color: #00796b;
    text-decoration: none;
  }
  a:hover {
    text-decoration: underline;
  }
</style>
<body>
  <h2>Вход</h2>
  <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
  <form method="post">
    <input type="text" name="username" required placeholder="Потребителско име"><br>
    <input type="password" name="password" required placeholder="Парола"><br>
    <input type="submit" value="Вход">
  </form>
  <p>Нямаш акаунт? <a href="register.php">Регистрация</a></p>
</body>
</html>
