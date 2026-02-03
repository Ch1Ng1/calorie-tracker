<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include("security_headers.php");
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
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Добави храна - Калории Тракер</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      margin: 0;
      padding: 0;
    }

    .add-container {
      flex: 1;
      max-width: 600px;
      margin: 30px auto;
      padding: 20px;
      width: 100%;
    }

    .add-form {
      background: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .add-form h2 {
      text-align: center;
      color: #00796b;
      margin-top: 0;
    }

    .form-group {
      margin-bottom: 20px;
      text-align: left;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }

    .form-group input[type="date"],
    .form-group input[type="text"],
    .form-group input[type="number"] {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
      box-sizing: border-box;
      transition: border-color 0.3s;
    }

    .form-group input:focus {
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

    .error-message {
      background-color: #ffebee;
      color: #c62828;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      border-left: 4px solid #c62828;
      text-align: left;
    }

    .info-text {
      font-size: 12px;
      color: #666;
      margin-top: 5px;
    }

    .form-note {
      background: #e8f5e9;
      padding: 15px;
      border-radius: 5px;
      margin-top: 20px;
      border-left: 4px solid #4caf50;
      color: #2e7d32;
      font-size: 14px;
      text-align: center;
    }

    .back-link {
      text-align: center;
      margin-top: 20px;
    }

    .back-link a {
      color: #00796b;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .back-link a:hover {
      color: #004d40;
      text-decoration: underline;
    }

    @media (max-width: 767px) {
      .add-container {
        margin: 15px auto;
        padding: 10px;
      }

      .add-form {
        padding: 25px;
      }

      .add-form h2 {
        font-size: 22px;
      }

      .form-group input {
        font-size: 16px;
        padding: 14px;
      }
    }

    @media (max-width: 479px) {
      .add-container {
        margin: 10px auto;
        padding: 8px;
      }

      .add-form {
        padding: 20px;
      }

      .add-form h2 {
        font-size: 18px;
      }

      .form-group {
        margin-bottom: 15px;
      }

      .form-group input {
        font-size: 16px;
        padding: 12px;
      }

      .form-group label {
        font-size: 14px;
        margin-bottom: 6px;
      }
    }
  </style>
</head>
<body>
  <?php include("header.php"); ?>

  <div class="add-container">
    <div class="add-form">
      <h2>➕ Добави новата храна</h2>

      <?php if ($error): ?>
        <div class="error-message">
            <?= $error ?>
        </div>
      <?php endif; ?>

      <form action="insert.php" method="post">
        <?php echo getCsrfField(); ?>

        <div class="form-group">
          <label for="date">Дата:</label>
          <input type="date" id="date" name="date" max="<?= date('Y-m-d') ?>" required>
          <div class="info-text">Можеш да въведеш дата на миналите дни</div>
        </div>

        <div class="form-group">
          <label for="food">Храна:</label>
          <input type="text" id="food" name="food" maxlength="50" 
                 pattern="[A-Za-z0-9А-Яа-я\s\-]+" required 
                 placeholder="Например: Пилешко филе със салата"
                 title="Само букви, цифри, пространство и хифен">
          <div class="info-text">Максимум 50 символа</div>
        </div>

        <div class="form-group">
          <label for="calories">Калории:</label>
          <input type="number" id="calories" name="calories" required min="5" max="5000" 
                 placeholder="Например: 250">
          <div class="info-text">Въведи броя на калориите (5 - 5000)</div>
        </div>

        <div class="form-group">
          <input type="submit" value="✓ Запиши храната">
        </div>
      </form>

      <div class="form-note">
        💡 <strong>Совет:</strong> Пази точност при броя на калориите за по-добрия резултат!
      </div>

      <div class="back-link">
        <a href="index.php">← Назад към начало</a>
      </div>
    </div>
  </div>

  <footer class="footer">
    <p>&copy; 2025 Калории Тракер | Всички права запазени</p>
  </footer>
</body>
</html>
