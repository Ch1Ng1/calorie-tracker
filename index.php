<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include("conf.php");

$conn = new mysqli($h, $u, $p, $db);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, date, food, calories FROM meals 
                       WHERE user_id = ? AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY)
                       ORDER BY date DESC, id DESC");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$today = date('Y-m-d');
$totalCaloriesToday = 0;
$pastMeals = [];
$todayMeals = [];

while ($row = $result->fetch_assoc()) {
    if ($row['date'] === $today) {
        $todayMeals[] = $row;
        $totalCaloriesToday += (int)$row['calories'];
    } else {
        $pastMeals[] = $row;
    }
}

// Затваряне на връзките с базата данни
// Вземаме данните за графиката
$conn = new mysqli($h, $u, $p, $db);
$stmt = $conn->prepare("SELECT DATE(date) as date, SUM(calories) as total_calories 
                       FROM meals 
                       WHERE user_id = ? 
                       AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)
                       GROUP BY DATE(date)
                       ORDER BY date ASC");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$chartResult = $stmt->get_result();

$dates = [];
$caloriesData = [];

while ($row = $chartResult->fetch_assoc()) {
    $dates[] = $row['date'];
    $caloriesData[] = $row['total_calories'];
}

$chartDates = json_encode($dates);
$chartCalories = json_encode($caloriesData);

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <title>Калории Тракер</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      .testimonials {
  background: #f9fbe7;
  padding: 30px;
  margin-top: 40px;
  border-top: 2px solid #cddc39;
  max-width: 800px;
  margin-left: auto;
  margin-right: auto;
  border-radius: 10px;
}
.testimonials h3 {
  margin-bottom: 20px;
  color: #827717;
}
.testimonial {
  margin-bottom: 20px;
  font-style: italic;
}
.testimonial span {
  display: block;
  margin-top: 5px;
  font-weight: bold;
  color: #616161;
}

.footer {
  background: #263238;
  color: #ccc;
  text-align: center;
  padding: 20px;
  margin-top: 40px;
  font-size: 14px;
}

    /* === ОБЩИ СТИЛОВЕ === */
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f0f4f8;
      color: #333;
      text-align: center;
    }

    /* === НАВИГАЦИЯ === */
    .navbar {
      background: #00796b;
      color: white;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }

    .navbar h2 {
      margin: 0;
    }

    .form-inline {
      display: flex;
      gap: 5px;
      flex-wrap: wrap;
      align-items: center;
    }

    .form-inline input {
      padding: 8px;
      border: none;
      border-radius: 4px;
    }

    .form-inline input[type="submit"] {
      background: #004d0cff;
      color: white;
      cursor: pointer;
    }

    .logout-btn {
      background: #28c689ff;
      color: white;
      padding: 8px 12px;
      border-radius: 4px;
      text-decoration: none;
      font-weight: bold;
      margin-left: 10px;
    }

    .logout-btn:hover {
      background: #b71c1c;
    }

    /* === ОСНОВНО РАЗПОЛОЖЕНИЕ === */
    .layout {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin: 30px auto;
      max-width: 1200px;
      justify-content: center;
    }

    .container {
      flex: 2;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      margin: 0 auto;
      overflow-y: scroll;
      max-height: 600px;
    }

    .sidebar {
      flex: 1;
      background: #fff3e0;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
      margin: 0 auto;
    }

    .sidebar h3 {
      color: #e65100;
    }

    .sidebar ul {
      padding-left: 20px;
      text-align: left;
    }

    .sidebar li {
      margin-bottom: 8px;
    }

    /* === ТАБЛИЦА === */
    table {
      width: 90%;
      border-collapse: collapse;
      margin: 20px auto;
    }

    th, td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
      text-align: left;
    }

    th {
      background: #e0f2f1;
    }

    /* === ГРАФИКА === */
    .chart {
      margin: 20px auto;
      text-align: center;
      width: 100%;
      max-width: 600px;
    }

    /* === ХАРМОНИКА === */
    .tips {
      margin: 0px auto;
      background: #e8f5e9;
      padding: 20px;
      border-radius: 8px;
      max-width: 600px;
    }

    .accordion-item {
      margin-bottom: 10px;
      text-align: left;
    }

    .accordion-header {
      background: #c8e6c9;
      border: none;
      padding: 10px 15px;
      width: 100%;
      text-align: left;
      font-weight: bold;
      cursor: pointer;
      border-radius: 5px;
    }

    .accordion-header.active {
      background: #a5d6a7;
    }

    .accordion-content {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      background: #f1f8e9;
      padding: 0 15px;
      border-radius: 0 0 5px 5px;
    }

    .accordion-content.open {
      padding: 10px 15px;
    }

    .meal-suggestions {
  background: #fffde7;
  padding: 30px;
  margin: 40px auto;
  border-top: 2px solid #fbc02d;
  max-width: 800px;
  border-radius: 10px;
}
.meal-suggestions h3 {
  margin-bottom: 20px;
  color: #f57f17;
}
.meal-suggestions ul {
  padding-left: 20px;
}
.meal-suggestions li {
  margin-bottom: 8px;
}
    .alert {
            background-color: #ffe0e0;
            color: #a00;
            padding: 15px;
            border: 1px solid #f5c2c2;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: Arial, sans-serif;
        }
    .delete-btn {
        color: #dc3545;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 4px;
    }
    .delete-btn:hover {
        background-color: #dc3545;
        color: white;
    }
    .export-btn {
        display: inline-block;
        background-color: #28a745;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        transition: background-color 0.3s;
    }
    .export-btn:hover {
        background-color: #218838;
    }
    .chart-container {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
  </style>

</head>
<body>
<?php if (!empty($error)): ?>
    <div class="alert">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>
  <div class="navbar">
     <a class="navbar-brand fw-bold" href="#page-top"><img class="nav-logo-size pe-1 d-inline-block align-text-top" src="https://i.postimg.cc/0Q3z3GRN/Screenshot-2025-10-06-113211.png" alt="Лого - Калориен Калкулатор"></a>
    <h2>Здравей, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
    <div class="form-inline">
      <form method="post" action="insert.php">
        <input type="date" name="date" max="<?= date('Y-m-d') ?>" required>
        <input type="text" name="food" maxlength="50" pattern="[A-Za-zА-Яа-я\s]+" required placeholder="Храна">
        <input type="number" name="calories" min="5" max="5000" required placeholder="Калории" aria-label="Калории">
        <input type="submit" value="Добави">
      </form>
      <a href="logout.php" class="logout-btn">Изход</a>
    </div>
  </div>

  <div class="layout">
    <div class="container">
      <h3>Твоите хранения </h3>
      <table>
        <tr><th>Дата</th><th>Храна</th><th>Калории</th><th>Действия</th></tr>

        <?php foreach ($pastMeals as $row): ?>
          <tr style="background:#f5f5f5;">
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td><?= htmlspecialchars($row['food']) ?></td>
            <td><?= htmlspecialchars($row['calories']) ?></td>
            <td><a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Сигурни ли сте?')" class="delete-btn">🗑️</a></td>
          </tr>
        <?php endforeach; ?>

        <?php foreach ($todayMeals as $row): ?>
          <tr style="background:#e8f5e9;">
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td><?= htmlspecialchars($row['food']) ?></td>
            <td><?= htmlspecialchars($row['calories']) ?></td>
            <td><a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Сигурни ли сте?')" class="delete-btn">🗑️</a></td>
          </tr>
        <?php endforeach; ?>

        <tr style="background:#e0f7fa;">
          <td colspan="2"><strong>Общо за днес (<?= $today ?>)</strong></td>
          <td><strong><?= $totalCaloriesToday ?> kcal</strong></td>
        </tr>
      </table>

     

      
    </div>

    <div  class="tips">
  <h3>💡 Съвети за здравословен живот</h3>
      <div class="chart-container" style="position: relative; height:400px; width:100%; margin: 20px 0;">
        <canvas id="calorieChart"></canvas>
      </div>
      <div style="text-align: center; margin: 20px 0;">
        <a href="export.php" class="export-btn">📊 Изтегли данните (CSV)</a>
      </div>  <div class="accordion">
    <div class="accordion-item">
      <button class="accordion-header">💧 Хидратация</button>
      <div class="accordion-content">
        Пий поне 2 литра вода на ден. Водата подпомага метаболизма, мозъчната функция и енергията.
      </div>
    </div>

    <div class="accordion-item">
      <button class="accordion-header">🥦 Хранене</button>
      <div class="accordion-content">
        Избягвай преработени храни и захар. Залагай на зеленчуци, пълнозърнести храни и балансирани порции.
      </div>
    </div>

    <div class="accordion-item">
      <button class="accordion-header">🚶‍♀️ Движение</button>
      <div class="accordion-content">
        Ходи поне 30 минути дневно. Леката активност подобрява кръвообращението и настроението.
      </div>
    </div>

    <div class="accordion-item">
      <button class="accordion-header">😴 Сън</button>
      <div class="accordion-content">
        Спи поне 7 часа на нощ. Качественият сън е ключов за възстановяване и хормонален баланс.
      </div>
    </div>

    <div class="accordion-item">
      <button class="accordion-header">🔥 Калории</button>
      <div class="accordion-content">
        Нормата на калории за възрастен човек е около <strong>2000 kcal</strong> на ден – стреми се към баланс между прием и разход.
      </div>
    </div>

    <div class="accordion-item">
      <button class="accordion-header">🧠 Навици</button>
      <div class="accordion-content">
        Изграждай устойчиви навици – малки стъпки всеки ден водят до големи резултати в дългосрочен план.
      </div>
        
    </div>
  </div>
</div>

  </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('calorieChart').getContext('2d');
    const dates = <?= $chartDates ?>;
    const calories = <?= $chartCalories ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Калории за ден',
                data: calories,
                borderColor: '#00796b',
                backgroundColor: 'rgba(0, 121, 107, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Калории за последния месец',
                    font: {
                        size: 16
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Калории'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Дата'
                    }
                }
            }
        }
    });
});</script>

<div class="meal-suggestions">
  <h3>🍽️ Препоръчани ястия</h3>
  <div class="accordion">
    <div class="accordion-item">
      <button class="accordion-header">🍳 Закуски (средно ~220 kcal)</button>
      <div class="accordion-content">
        <ul>
          <li>Овесени ядки с банан и мед (~250 kcal)</li>
          <li>Кисело мляко с ленено семе (~200 kcal)</li>
          <li>Пълнозърнест тост с авокадо (~210 kcal)</li>
        </ul>
      </div>
    </div>

    <div class="accordion-item">
      <button class="accordion-header">🥗 Обяди (средно ~350 kcal)</button>
      <div class="accordion-content">
        <ul>
          <li>Печено пилешко филе със салата (~370 kcal)</li>
          <li>Супа от тиквички и моркови (~320 kcal)</li>
          <li>Ориз със зеленчуци и тофу (~360 kcal)</li>
        </ul>
      </div>
    </div>

    <div class="accordion-item">
      <button class="accordion-header">🍲 Вечери (средно ~300 kcal)</button>
      <div class="accordion-content">
        <ul>
          <li>Омлет със спанак и гъби (~280 kcal)</li>
          <li>Печена сьомга с броколи (~310 kcal)</li>
          <li>Салата с нахут и авокадо (~310 kcal)</li>
        </ul>
      </div>
    </div>
  </div>
</div>




  <div class="testimonials">
  <h3>💬 Отзиви от потребители</h3>
  <div class="testimonial">
    <p>„Много ми помага да следя калориите си – интерфейсът е супер лесен!“</p>
    <span>— Мария, София</span>
  </div>
  <div class="testimonial">
    <p>„Благодарение на този тракер свалих 5 кг за месец!“</p>
    <span>— Иван, Пловдив</span>
  </div>
  <div class="testimonial">
    <p>„Обичам съветите и леките рецепти – точно каквото ми трябваше.“</p>
    <span>— Елена, Варна</span>
  </div>
</div>

<footer class="footer">
  <p>&copy; 2025 Калории Тракер | Всички права запазени</p>
</footer>

  
  <script>
    document.querySelectorAll('.accordion-header').forEach(header => {
      header.addEventListener('click', () => {
        header.classList.toggle('active');
        const content = header.nextElementSibling;
        if (content.style.maxHeight) {
          content.style.maxHeight = null;
          content.classList.remove('open');
        } else {
          content.style.maxHeight = content.scrollHeight + "px";
          content.classList.add('open');
        }
      });
    });
  </script>



</body>
</html>
