<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include("security_headers.php");
include("conf.php");
include("csrf.php");

$conn = new mysqli($h, $u, $p, $db);
$user_id = $_SESSION['user_id'];

// Вземи текущата цел (ако колоната съществува)
$currentGoal = 2000; // default
$columnExists = false;

try {
    $stmt = $conn->prepare("SELECT daily_goal FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $currentGoal = $row['daily_goal'] ?? 2000;
            $columnExists = true;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Колоната daily_goal още не съществува
    $columnExists = false;
}

// Обработка на формата
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daily_goal'])) {
    // CSRF проверка временно деактивирана
    // if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    //     $error = "❌ Сигурностна проверка неуспешна. Моля, опитайте отново.";
    // } else
    if (!$columnExists) {
        $error = "Моля, първо изпълни SQL скрипта update_database.sql за да добавиш тази функция!";
    } else {
        $newGoal = (int)$_POST['daily_goal'];
        if ($newGoal >= 500 && $newGoal <= 5000) {
            $stmt = $conn->prepare("UPDATE users SET daily_goal = ? WHERE id = ?");
            $stmt->bind_param("ii", $newGoal, $user_id);
            $stmt->execute();
            $stmt->close();
            $currentGoal = $newGoal;
            $message = "✓ Целта е обновена успешно!";
        } else {
            $error = "Моля въведи валидна цел между 500 и 5000 калории.";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - Калории Тракер</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 20px;
        }
        .navbar {
            background: #00796b;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            background: #004d40;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .navbar a:hover {
            background: #00251a;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            color: #00796b;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input[type="number"]:focus {
            border-color: #00796b;
            outline: none;
        }
        .btn {
            background: #00796b;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #004d40;
        }
        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #4caf50;
        }
        .info-box h3 {
            margin-top: 0;
            color: #2e7d32;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .info-box li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>⚙️ Настройки</h2>
        <a href="index.php">← Назад към начало</a>
    </div>

    <div class="container">
        <?php if (!$columnExists): ?>
            <div class="error">
                <strong>⚠️ Важно!</strong><br>
                Колоната 'daily_goal' не съществува в базата данни.<br>
                Моля, изпълни SQL скрипта: <code>update_database.sql</code> в phpMyAdmin или MySQL конзолата.<br>
                След това презареди страницата.
            </div>
        <?php endif; ?>
        
        <?php if (isset($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2>Персонализирай дневната си цел</h2>
        
        <form method="post">
            <?php echo getCsrfField(); ?>
            <div class="form-group">
                <label for="daily_goal">Дневна цел за калории (kcal):</label>
                <input type="number" 
                       id="daily_goal" 
                       name="daily_goal" 
                       value="<?= $currentGoal ?>" 
                       min="500" 
                       max="5000" 
                       step="50" 
                       required>
            </div>
            <button type="submit" class="btn">💾 Запази промените</button>
        </form>

        <div class="info-box">
            <h3>💡 Препоръки за дневна норма:</h3>
            <ul>
                <li><strong>Жени:</strong> 1800-2200 kcal (в зависимост от активността)</li>
                <li><strong>Мъже:</strong> 2200-2800 kcal (в зависимост от активността)</li>
                <li><strong>За отслабване:</strong> Намали с 300-500 kcal от нормата</li>
                <li><strong>За качване на тегло:</strong> Увеличи с 300-500 kcal</li>
                <li><strong>Спортуващи:</strong> Увеличи с 500-1000 kcal в зависимост от натоварването</li>
            </ul>
            <p><em>Текуща цел: <strong><?= $currentGoal ?> kcal/ден</strong></em></p>
        </div>
    </div>
</body>
</html>
