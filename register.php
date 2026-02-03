<?php
session_start();
include("security_headers.php");
include("conf.php");
include("csrf.php");

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF проверка временно деактивирана
    // if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    //     $errors[] = "❌ Сигурностна проверка неуспешна. Моля, опитайте отново.";
    // }
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // Валидация
    if (strlen($username) < 3) {
        $errors[] = "Потребителското име трябва да е поне 3 символа.";
    }
    if (strlen($username) > 20) {
        $errors[] = "Името трябва да е до 20 символа.";
    }
    if (!preg_match('/^[A-Za-zА-Яа-я0-9\- ]+$/u', $username)) {
        $errors[] = "Името не трябва да съдържа специални символи.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Паролата трябва да е поне 6 символа.";
    }
    if ($password !== $confirm) {
        $errors[] = "Паролите не съвпадат.";
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

            // Use password_hash for secure password storage
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed);
            $stmt->execute();

            session_regenerate_id(true);
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Регистрация - Калории Тракер</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      margin: 0;
      padding: 0;
    }

    .register-container {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .register-form {
      background: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 450px;
    }

    .register-form h2 {
      margin-top: 0;
      text-align: center;
      color: #00796b;
      margin-bottom: 30px;
    }

    .form-group {
      margin-bottom: 15px;
      text-align: left;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }

    .form-group input[type="text"],
    .form-group input[type="password"] {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
      box-sizing: border-box;
      transition: border-color 0.3s;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="password"]:focus {
      outline: none;
      border-color: #00796b;
      box-shadow: 0 0 5px rgba(0, 121, 107, 0.2);
    }

    .form-group input[type="submit"] {
      width: 100%;
      padding: 12px;
      background: #00796b;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: background-color 0.3s, transform 0.2s;
    }

    .form-group input[type="submit"]:hover {
      background: #004d40;
      transform: translateY(-2px);
    }

    .form-group input[type="submit"]:active {
      transform: translateY(0);
    }

    .form-links {
      text-align: center;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #eee;
    }

    .form-links a {
      color: #00796b;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .form-links a:hover {
      color: #004d40;
      text-decoration: underline;
    }

    .error-list {
      background-color: #ffebee;
      color: #c62828;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      border-left: 4px solid #c62828;
    }

    .error-list ul {
      margin: 0;
      padding-left: 20px;
    }

    .error-list li {
      margin-bottom: 8px;
    }

    .info-text {
      font-size: 12px;
      color: #666;
      margin-top: 5px;
    }

    @media (max-width: 767px) {
      .register-container {
        padding: 15px;
      }

      .register-form {
        padding: 25px;
      }

      .register-form h2 {
        font-size: 24px;
        margin-bottom: 25px;
      }

      .form-group input[type="text"],
      .form-group input[type="password"],
      .form-group input[type="submit"] {
        font-size: 16px;
        padding: 14px;
      }
    }

    @media (max-width: 479px) {
      .register-container {
        padding: 10px;
      }

      .register-form {
        padding: 20px;
      }

      .register-form h2 {
        font-size: 20px;
        margin-bottom: 20px;
      }

      .form-group {
        margin-bottom: 12px;
      }

      .form-group input[type="text"],
      .form-group input[type="password"],
      .form-group input[type="submit"] {
        font-size: 16px;
        padding: 12px;
      }
    }
  </style>
</head>
<body>
  <?php include("header.php"); ?>

  <div class="register-container">
    <div class="register-form">
      <h2>📝 Регистрация</h2>
      
      <?php if (!empty($errors)): ?>
        <div class="error-list">
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post">
        <?php echo getCsrfField(); ?>
        
        <div class="form-group">
          <label for="username">Потребителско име:</label>
          <input type="text" id="username" name="username" required maxlength="20" 
                 placeholder="Въведи потребителско име" 
                 pattern="[A-Za-zА-Яа-я0-9\- ]+"
                 title="Само букви, цифри, пространство и хифен">
          <div class="info-text">Минимум 3, максимум 20 символа</div>
        </div>

        <div class="form-group">
          <label for="password">Парола:</label>
          <input type="password" id="password" name="password" required 
                 placeholder="Въведи парола" 
                 minlength="6">
          <div class="info-text">Минимум 6 символа</div>
        </div>

        <div class="form-group">
          <label for="confirm">Потвърди паролата:</label>
          <input type="password" id="confirm" name="confirm" required 
                 placeholder="Потвърди паролата" 
                 minlength="6">
        </div>

        <div class="form-group">
          <input type="submit" value="Регистрирай се">
        </div>
      </form>

      <div class="form-links">
        <p>Вече имаш акаунт? <a href="login.php">Влез тук</a></p>
      </div>
    </div>
  </div>

  <footer class="footer">
    <p>&copy; 2025 Калории Тракер | Всички права запазени</p>
  </footer>
</body>
</html>
