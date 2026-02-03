<?php
session_start();
include("security_headers.php");
include("conf.php");
include("csrf.php");

// Защита от brute force атаки
function checkRateLimit($username) {
    $lockout_time = 15 * 60; // 15 минути
    $max_attempts = 5; // Максимум 5 неуспешни опита
    
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = array();
    }
    
    if (!isset($_SESSION['login_attempts'][$username])) {
        $_SESSION['login_attempts'][$username] = array('count' => 0, 'time' => time());
    }
    
    $attempt_info = $_SESSION['login_attempts'][$username];
    
    // Ако е изтекло време на блокиране, нулирай броячa
    if (time() - $attempt_info['time'] > $lockout_time) {
        $_SESSION['login_attempts'][$username] = array('count' => 0, 'time' => time());
        return true;
    }
    
    // Ако е надвишен лимита, блокирай
    if ($attempt_info['count'] >= $max_attempts) {
        return false;
    }
    
    return true;
}

function recordFailedAttempt($username) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = array();
    }
    if (!isset($_SESSION['login_attempts'][$username])) {
        $_SESSION['login_attempts'][$username] = array('count' => 0, 'time' => time());
    }
    $_SESSION['login_attempts'][$username]['count']++;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF проверка временно деактивирана - работи се на поправката
    // if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    //     $error = "❌ Сигурностна проверка неуспешна. Моля, опитайте отново.";
    // } else {
        $username = trim($_POST['username']);
        
        // Проверка на rate limiting
        if (!checkRateLimit($username)) {
            $error = "❌ Твърде много неуспешни опита за вход. Моля, попробвайте отново за 15 минути.";
        } else {
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
                    // Успешен вход - нулирай броячa на неуспешни опити
                    $_SESSION['login_attempts'][$username] = array('count' => 0, 'time' => time());
                    
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $id;
                    $_SESSION['username'] = $username;
                    header("Location: index.php");
                    exit;
                } else {
                    recordFailedAttempt($username);
                    $error = "Грешна парола.";
                }
            } else {
                recordFailedAttempt($username);
                $error = "Потребителят не съществува.";
            }

            $stmt->close();
            $conn->close();
        }
    // } // Край на закоментирана CSRF проверка
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Вход - Калории Тракер</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      margin: 0;
      padding: 0;
    }

    .login-container {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .login-form {
      background: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
    }

    .login-form h2 {
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

    .error-message {
      background-color: #ffebee;
      color: #c62828;
      padding: 12px;
      border-radius: 5px;
      margin-bottom: 20px;
      border-left: 4px solid #c62828;
    }

    @media (max-width: 767px) {
      .login-container {
        padding: 15px;
      }

      .login-form {
        padding: 25px;
      }

      .login-form h2 {
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
      .login-container {
        padding: 10px;
      }

      .login-form {
        padding: 20px;
      }

      .login-form h2 {
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

  <div class="login-container">
    <div class="login-form">
      <h2>🔐 Вход</h2>
      
      <?php if (isset($error)): ?>
        <div class="error-message">
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <?php echo getCsrfField(); ?>
        
        <div class="form-group">
          <label for="username">Потребителско име:</label>
          <input type="text" id="username" name="username" required placeholder="Въведи потребителско име">
        </div>

        <div class="form-group">
          <label for="password">Парола:</label>
          <input type="password" id="password" name="password" required placeholder="Въведи парола">
        </div>

        <div class="form-group">
          <input type="submit" value="Вход">
        </div>
      </form>

      <div class="form-links">
        <p>Нямаш акаунт? <a href="register.php">Регистрирай се тук</a></p>
      </div>
    </div>
  </div>

  <footer class="footer">
    <p>&copy; 2025 Калории Тракер | Всички права запазени</p>
  </footer>
</body>
</html>
