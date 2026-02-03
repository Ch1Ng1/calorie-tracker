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
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Настройки - Калории Тракер</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .settings-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .settings-form {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }

        .settings-form h2 {
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

        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        .form-group input[type="number"]:focus,
        .form-group select:focus {
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

        .success-message {
            background-color: #c8e6c9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
        }

        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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
            .settings-container {
                padding: 15px;
            }

            .settings-form {
                padding: 25px;
            }

            .settings-form h2 {
                font-size: 22px;
            }

            .form-group input,
            .form-group select {
                font-size: 16px;
                padding: 14px;
            }
        }

        @media (max-width: 479px) {
            .settings-container {
                padding: 10px;
            }

            .settings-form {
                padding: 20px;
            }

            .settings-form h2 {
                font-size: 20px;
            }

            .form-group input,
            .form-group select {
                font-size: 16px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>

    <div class="settings-container">
        <div class="settings-form">
            <h2>⚙️ Настройки</h2>

            <?php if (isset($success)): ?>
                <div class="success-message">
                    ✓ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php echo getCsrfField(); ?>

                <div class="form-group">
                    <label for="daily_goal">Дневна дневна цел (Kcal):</label>
                    <input type="number" id="daily_goal" name="daily_goal" min="500" max="5000" 
                           value="<?= htmlspecialchars($currentGoal) ?>" required>
                    <div class="info-text">Препоръчана стойност: 2000 kcal за възрастни</div>
                </div>

                <div class="form-group">
                    <input type="submit" value="💾 Запази настройките">
                </div>
            </form>

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
