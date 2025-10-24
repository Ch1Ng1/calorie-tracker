<?php
session_start();
include("conf.php");

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // Валидация
    if (strlen($username) < 3) {
        $errors[] = "Потребителското име трябва да е поне 3 символа.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Паролата трябва да е поне 6 символа.";
    }
    if ($password !== $confirm) {
        $errors[] = "Паролите не съвпадат.";
    }
    if (strlen($username) > 20) {
        $errors[] = "Грешка: Името трябва да е до 20 символа.";
}

if (!preg_match('/^[A-Za-zА-Яа-я0-9\- ]+$/u', $username)) {
    die("Грешка: Името не трябва да съдържа специални символи.");
}
    if (empty($errors)) {
        $conn = new mysqli($h, $u, $p, $db);

        // Проверка дали потребителят вече съществува
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Потребителското име вече съществува.";
        } else {
            $stmt->close();

            // ⚠️ Тук използваме SHA256 (по твое желание без password_hash)
            $hashed = hash("sha256", $password);

            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed);
            $stmt->execute();

            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;

            header("Location: index.php");
            exit;
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <title>Регистрация</title>
</head>
<style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f0f4f8;
      color: #333;
      text-align: center;
      margin: 0;
      padding: 20px;
    }
    form {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      display: inline-block;
      margin-top: 20px;
    }
    input[type="text"], input[type="password"] {
      width: 200px;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    input[type="submit"] {
      background: #00796b;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
    }
    input[type="submit"]:hover {
      background: #005f56;
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
  <h2>Регистрация</h2>
  <?php if (!empty($errors)): ?>
    <ul style="color:red;">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
    
  <?php endif; ?>
  

  <form method="post">
    <input type="text" name="username" required maxlength="20" required placeholder="Въведете потребителско име"
       pattern="[A-Za-zА-Яа-я0-9\- ]{1,40}"
       title="Името трябва да е до 20 символа и без специални знаци"><br>
    <input type="password" maxlength="10" name="password" required placeholder="Парола"><br>
    <input type="password" maxlength="10" name="confirm" required placeholder="Потвърди паролата"><br>
    <input type="submit" value="Регистрирай се">
  </form>
  <p>Вече имаш акаунт? <a href="login.php">Вход</a></p>
</body>
</html>
