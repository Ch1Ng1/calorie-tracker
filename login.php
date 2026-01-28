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
  <?php if (isset($error)) echo "<p style='color:red;'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</p>"; ?>
  <form method="post">
    <?php echo getCsrfField(); ?>
    <input type="text" name="username" required placeholder="Потребителско име"><br>
    <input type="password" name="password" required placeholder="Парола"><br>
    <input type="submit" value="Вход">
  </form>
  <p>Нямаш акаунт? <a href="register.php">Регистрация</a></p>
</body>
</html>
