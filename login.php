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
  // Обнови времевия маркер при неуспешен опит
  $_SESSION['login_attempts'][$username]['time'] = time();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Възстановена CSRF проверка
  if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $error = "❌ Сигурностна проверка неуспешна. Моля, опитайте отново.";
  } else {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING) ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
      $error = 'Моля, въведете потребителско име.';
    } else {
      // Проверка на rate limiting
      if (!checkRateLimit($username)) {
        $error = "❌ Твърде много неуспешни опита за вход. Моля, попробвайте отново за 15 минути.";
      } else {
        $conn = new mysqli($h, $u, $p, $db);

        if ($conn->connect_error) {
          error_log('DB connection error: ' . $conn->connect_error);
          $error = 'Вътрешна сървърна грешка.';
        } else {
          $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
          if (!$stmt) {
            error_log('DB prepare failed: ' . $conn->error);
            $error = 'Вътрешна сървърна грешка.';
          } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            // Единно съобщение за невалидни данни (предотвратява потребителско изброяване)
            $genericInvalid = 'Невалидни идентификационни данни.';

            if ($stmt->num_rows > 0) {
              $stmt->bind_result($id, $hashed);
              $stmt->fetch();

              if (password_verify($password, $hashed)) {
                // Успешен вход - нулирай броячa на неуспешни опити
                $_SESSION['login_attempts'][$username] = array('count' => 0, 'time' => time());

                session_regenerate_id(true);
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $stmt->close();
                $conn->close();
                header("Location: index.php");
                exit;
              } else {
                recordFailedAttempt($username);
                $error = $genericInvalid;
              }
            } else {
              recordFailedAttempt($username);
              $error = $genericInvalid;
            }

            $stmt->close();
          }
          $conn->close();
        }
      }
    }
  }
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
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      background-attachment: fixed;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
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
      border-radius: 15px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
      animation: fadeInUp 0.6s ease-out;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-form h2 {
      margin-top: 0;
      text-align: center;
      color: #667eea;
      margin-bottom: 10px;
      font-size: 28px;
      font-weight: 700;
    }

    .welcome-text {
      text-align: center;
      color: #666;
      margin-bottom: 30px;
      font-size: 16px;
      line-height: 1.5;
    }

    .form-group {
      margin-bottom: 20px;
      text-align: left;
      position: relative;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
      font-size: 14px;
    }

    .form-group input[type="text"],
    .form-group input[type="password"] {
      width: 100%;
      padding: 12px 12px 12px 40px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 14px;
      box-sizing: border-box;
      transition: all 0.3s ease;
      background: #f8f9fa;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="password"]:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      background: white;
    }

    .form-group::before {
      content: '';
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 20px;
      height: 20px;
      background-size: contain;
      background-repeat: no-repeat;
      opacity: 0.6;
    }

    .form-group:nth-child(1)::before {
      background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDJDMTMuMSAyIDE0IDIuOSAxNCA0VjE2QzE0IDE3LjEgMTMuMSAxOCA5IDE4VjIwQzE0LjQgMjAgMTYgMTguNCAxNiAxNkgyMEMxNiAxMy42IDE0LjQgMTIgMTIgMTJDMTMuMSAxMiAxNCAxMS4xIDE0IDEwVjRDMTQgMi45IDEzLjEgMiAxMiAyWk0xMiA0QzEyLjU1IDQgMTMgNC40NSAxMyA1VjEwQzEzIDEwLjU1IDEyLjU1IDExIDEyIDExQzExLjQ1IDExIDExIDEwLjU1IDExIDEwVjVDMTEgNC40NSAxMS40NSA0IDEyIDRaIiBmaWxsPSIjNjY3ZWVhIi8+Cjwvc3ZnPgo=');
    }

    .form-group:nth-child(2)::before {
      background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDJDMTMuMSAyIDE0IDIuOSAxNCA0VjE2QzE0IDE3LjEgMTMuMSAxOCA5IDE4VjIwQzE0LjQgMjAgMTYgMTguNCAxNiAxNkgyMEMxNiAxMy42IDE0LjQgMTIgMTIgMTJDMTMuMSAxMiAxNCAxMS4xIDE0IDEwVjRDMTQgMi45IDEzLjEgMiAxMiAyWk0xMiA0QzEyLjU1IDQgMTMgNC40NSAxMyA1VjEwQzEzIDEwLjU1IDEyLjU1IDExIDEyIDExQzExLjQ1IDExIDExIDEwLjU1IDExIDEwVjVDMTEgNC40NSAxMS40NSA0IDEyIDRaIiBmaWxsPSIjNjY3ZWVhIi8+Cjwvc3ZnPgo=');
    }

    .form-group input[type="submit"] {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .form-group input[type="submit"]:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .form-group input[type="submit"]:active {
      transform: translateY(0);
    }

    .form-links {
      text-align: center;
      margin-top: 25px;
      padding-top: 20px;
      border-top: 1px solid #e1e5e9;
    }

    .form-links a {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .form-links a:hover {
      color: #764ba2;
      text-decoration: underline;
    }

    .error-message {
      background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
      color: #c62828;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #c62828;
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    @media (max-width: 767px) {
      .login-container {
        padding: 15px;
      }

      .login-form {
        padding: 30px;
      }

      .login-form h2 {
        font-size: 24px;
        margin-bottom: 20px;
      }

      .welcome-text {
        font-size: 14px;
      }

      .form-group input[type="text"],
      .form-group input[type="password"],
      .form-group input[type="submit"] {
        font-size: 16px;
        padding: 14px 14px 14px 40px;
      }
    }

    @media (max-width: 479px) {
      .login-container {
        padding: 10px;
      }

      .login-form {
        padding: 25px;
      }

      .login-form h2 {
        font-size: 22px;
        margin-bottom: 15px;
      }

      .welcome-text {
        font-size: 13px;
      }

      .form-group {
        margin-bottom: 15px;
      }

      .form-group input[type="text"],
      .form-group input[type="password"],
      .form-group input[type="submit"] {
        font-size: 16px;
        padding: 12px 12px 12px 35px;
      }

      .form-group::before {
        left: 10px;
        width: 18px;
        height: 18px;
      }
    }
  </style>
</head>
<body>
  <?php include("header.php"); ?>

  <div class="login-container">
    <div class="login-form">
      <h2>🔐 Вход</h2>
      <p class="welcome-text">Добре дошъл обратно! Влез в акаунта си и продължи да следиш калориите си. 📊</p>
      
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
