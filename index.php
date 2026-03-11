<?php
include("session_config.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include("security_headers.php");
include("conf.php");

$conn = new mysqli($h, $u, $p, $db);
$user_id = $_SESSION['user_id'];

// Вземи дневната цел на потребителя (ако колоната съществува)
$dailyGoal = 2000; // default
$stmtGoal = $conn->prepare("SELECT daily_goal FROM users WHERE id = ?");
if ($stmtGoal) {
  $stmtGoal->bind_param("i", $user_id);
  $stmtGoal->execute();
  $goalResult = $stmtGoal->get_result();
  if ($goalRow = $goalResult->fetch_assoc()) {
    $dailyGoal = $goalRow['daily_goal'] ?? 2000;
  }
  $stmtGoal->close();
}

// Месечна статистика
$stmtMonth = $conn->prepare("SELECT SUM(calories) as total FROM meals WHERE user_id = ? AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)");
$stmtMonth->bind_param("i", $user_id);
$stmtMonth->execute();
$monthResult = $stmtMonth->get_result();
$monthTotal = $monthResult->fetch_assoc()['total'] ?? 0;
$stmtMonth->close();

// Подгответе променливите за дневна/седмична статистика и за график
$today = date('Y-m-d');

// Седмичен сбор (последните 7 дни)
$weekTotal = 0;
$stmtWeek = $conn->prepare("SELECT SUM(calories) as total FROM meals WHERE user_id = ? AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)");
if ($stmtWeek) {
  $stmtWeek->bind_param("i", $user_id);
  $stmtWeek->execute();
  $res = $stmtWeek->get_result();
  $weekTotal = $res->fetch_assoc()['total'] ?? 0;
  $stmtWeek->close();
}

// Днес: записи и сбор
$todayMeals = [];
$totalCaloriesToday = 0;
$stmtTodaySum = $conn->prepare("SELECT SUM(calories) as total FROM meals WHERE user_id = ? AND date = ?");
if ($stmtTodaySum) {
  $stmtTodaySum->bind_param("is", $user_id, $today);
  $stmtTodaySum->execute();
  $res = $stmtTodaySum->get_result();
  $totalCaloriesToday = $res->fetch_assoc()['total'] ?? 0;
  $stmtTodaySum->close();
}

$stmtToday = $conn->prepare("SELECT id, date, food, calories FROM meals WHERE user_id = ? AND date = ? ORDER BY id DESC");
if ($stmtToday) {
  $stmtToday->bind_param("is", $user_id, $today);
  $stmtToday->execute();
  $result = $stmtToday->get_result();
  while ($r = $result->fetch_assoc()) { $todayMeals[] = $r; }
  $stmtToday->close();
}

// Минали хранения (без днешните)
$pastMeals = [];
$stmtPast = $conn->prepare("SELECT id, date, food, calories FROM meals WHERE user_id = ? AND date < ? ORDER BY date DESC, id DESC LIMIT 100");
if ($stmtPast) {
  $stmtPast->bind_param("is", $user_id, $today);
  $stmtPast->execute();
  $res = $stmtPast->get_result();
  while ($r = $res->fetch_assoc()) { $pastMeals[] = $r; }
  $stmtPast->close();
}

// Данни за график: сбор по дати за последните 30 дни
$dateSums = [];
$stmtChart = $conn->prepare("SELECT date, SUM(calories) as total FROM meals WHERE user_id = ? AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY) GROUP BY date ORDER BY date ASC");
if ($stmtChart) {
  $stmtChart->bind_param("i", $user_id);
  $stmtChart->execute();
  $res = $stmtChart->get_result();
  while ($r = $res->fetch_assoc()) { $dateSums[$r['date']] = (int)$r['total']; }
  $stmtChart->close();
}

$dates = [];
$chartCaloriesArr = [];
for ($i = 29; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-{$i} days"));
  $dates[] = $d;
  $chartCaloriesArr[] = $dateSums[$d] ?? 0;
}

$chartDates = json_encode($dates);
$chartCalories = json_encode($chartCaloriesArr);

// Средна дневна норма за месец (изчислява се спрямо 30 дни)
$avgDaily = round($monthTotal / 30);

// Затваряне на връзката
$conn->close();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <title>Калории Тракер - Моите Калории</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
      /* === INDEX.PHP специфичен дизайн === */
      /* Тъмен оранжев градиент фон (престояща, покрива цялата страница) */
      body {
        background: linear-gradient(135deg, #2b1100 0%, #7a2f00 35%, #ff7a00 75%);
        background-attachment: fixed;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
      }

      .index-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
      }

      .statistics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
        width: 100%;
      }

      .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s, box-shadow 0.3s;
      }

      .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
      }

      .stat-card.pink { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
      .stat-card.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

      .stat-label {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 8px;
      }

      .stat-value {
        font-size: 28px;
        font-weight: bold;
        margin: 8px 0;
      }

      .stat-unit {
        font-size: 12px;
        opacity: 0.8;
      }

      .meals-table {
        width: 100%;
        overflow-x: auto;
      }

      .meals-table table {
        width: 100%;
        min-width: 300px;
      }

      .progress-section {
        margin: 25px auto;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 900px;
      }

      .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
      }

      .progress-bar-container {
        width: 100%;
        background: #e0e0e0;
        border-radius: 10px;
        height: 25px;
        overflow: hidden;
        margin-bottom: 10px;
      }

      .progress-bar-fill {
        height: 100%;
        transition: width 0.5s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 12px;
      }

      .content-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin: 20px auto;
        width: 100%;
        max-width: 1000px;
      }

      .content-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 900px;
      }

      .testimonials, .meal-suggestions {
        background-color: #f9fbe7;
        border-top: 3px solid #cddc39;
        padding: 25px;
        border-radius: 10px;
        margin: 25px auto;
        width: 100%;
        max-width: 900px;
        text-align: center;
      }

      .meal-suggestions {
        background-color: #fffde7;
        border-top-color: #fbc02d;
      }

      .footer {
        background: #263238;
        color: #ccc;
        text-align: center;
        padding: 20px;
        margin-top: 40px;
        font-size: 14px;
      }

      .chart-container {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin: 20px auto;
        position: relative;
        height: 400px;
        width: 100%;
      }

      /* Ensure content boxes stay readable on dark background */
      .content-box, .progress-section, .meal-suggestions, .testimonials {
        background: rgba(255,255,255,0.98);
      }

      /* Mobile Responsive */
      @media (max-width: 767px) {
        .index-container {
          padding: 10px;
        }

        .statistics-grid {
          grid-template-columns: 1fr;
          gap: 10px;
          margin-bottom: 15px;
        }

        .stat-card {
          padding: 15px;
        }

        .stat-value {
          font-size: 24px;
        }

        .content-layout {
          grid-template-columns: 1fr;
          gap: 15px;
        }

        .meals-table {
          overflow-x: auto;
        }

        .chart-container {
          height: 250px;
          padding: 15px;
        }

        .progress-header {
          flex-direction: column;
          align-items: flex-start;
        }

        .testimonials, .meal-suggestions {
          padding: 15px;
          margin: 15px 0;
        }

        table {
          font-size: 12px;
        }

        th, td {
          padding: 8px 5px;
        }
      }

      @media (max-width: 479px) {
        .index-container {
          padding: 8px;
        }

        .stat-card {
          padding: 12px;
        }

        .stat-value {
          font-size: 20px;
        }

        .stat-label {
          font-size: 12px;
        }

        table {
          font-size: 11px;
        }

        th, td {
          padding: 6px 3px;
        }

        .chart-container {
          height: 200px;
        }

        .progress-section {
          padding: 15px;
          margin: 15px 0;
        }
      }
  </head>
<body>
  <?php include("header.php"); ?>

  <div class="index-container">
    <?php if (!empty($error)): ?>
      <div class="alert">
          <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Быстрый ввод -->
    <div class="content-box" style="margin-bottom: 20px;">
      <h3>➕ Добави храна</h3>
      <form method="post" action="insert.php" style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; align-items: flex-end;">
        <div style="display: flex; flex-direction: column;">
          <label for="date" style="font-weight: bold; margin-bottom: 6px; color: #333; font-size: 14px;">Дата:</label>
          <input type="date" name="date" id="date" max="<?= date('Y-m-d') ?>" required style="padding: 11px 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px; transition: border-color 0.3s; font-family: inherit;" onfocus="this.style.borderColor='#00796b';" onblur="this.style.borderColor='#ddd';">
        </div>
        <div style="display: flex; flex-direction: column; flex: 1; min-width: 150px;">
          <label for="food" style="font-weight: bold; margin-bottom: 6px; color: #333; font-size: 14px;">Храна:</label>
          <input type="text" name="food" id="food" maxlength="50" pattern="[A-Za-zА-Яа-я\s]+" required placeholder="Въведи храна" style="padding: 11px 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px; transition: border-color 0.3s; font-family: inherit;" onfocus="this.style.borderColor='#00796b';" onblur="this.style.borderColor='#ddd';">
        </div>
        <div style="display: flex; flex-direction: column; min-width: 100px;">
          <label for="calories" style="font-weight: bold; margin-bottom: 6px; color: #333; font-size: 14px;">Калории:</label>
          <input type="number" name="calories" id="calories" min="5" max="5000" required placeholder="Калории" style="padding: 11px 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px; transition: border-color 0.3s; font-family: inherit;" onfocus="this.style.borderColor='#00796b';" onblur="this.style.borderColor='#ddd';">
        </div>
        <button type="submit" class="btn" style="align-self: flex-end; margin-bottom: 0;">✓ Добави</button>
      </form>
    </div>

    <!-- Статистика -->
    <div class="statistics-grid">
      <div class="stat-card">
        <div class="stat-label">📅 Седмица</div>
        <div class="stat-value"><?= number_format($weekTotal) ?></div>
        <div class="stat-unit">kcal общо</div>
      </div>
      
      <div class="stat-card pink">
        <div class="stat-label">📊 Месец</div>
        <div class="stat-value"><?= number_format($monthTotal) ?></div>
        <div class="stat-unit">kcal общо</div>
      </div>
      
      <div class="stat-card blue">
        <div class="stat-label">📈 Средно дневно</div>
        <div class="stat-value"><?= $avgDaily ?></div>
        <div class="stat-unit">kcal/ден</div>
      </div>
    </div>

    <!-- Таблица с храни -->
    <div class="content-box">
      <h3>🍽️ Твоите хранения</h3>
      <div class="meals-table">
        <table>
          <thead>
            <tr>
              <th>Дата</th>
              <th>Храна</th>
              <th>Калории</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($todayMeals as $row): ?>
              <tr style="background:#e8f5e9;">
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['food']) ?></td>
                <td><?= htmlspecialchars($row['calories']) ?></td>
                <td><a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Сигурни ли сте?')" class="delete-btn">🗑️ Изтрий</a></td>
              </tr>
            <?php endforeach; ?>

            <?php foreach ($pastMeals as $row): ?>
              <tr style="background:#f5f5f5;">
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['food']) ?></td>
                <td><?= htmlspecialchars($row['calories']) ?></td>
                <td><a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Сигурни ли сте?')" class="delete-btn">🗑️ Изтрий</a></td>
              </tr>
            <?php endforeach; ?>

            <tr style="background:#e0f7fa; font-weight: bold;">
              <td colspan="2">Общо за днес (<?= $today ?>)</td>
              <td colspan="2"><?= $totalCaloriesToday ?> kcal</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Прогрес -->
    <div class="progress-section">
      <?php 
        $progress = min(100, ($totalCaloriesToday / $dailyGoal) * 100);
        $progressColor = $progress > 100 ? '#f44336' : ($progress > 80 ? '#ff9800' : '#4caf50');
      ?>
      <div class="progress-header">
        <span style="font-weight: bold; font-size: 16px;">📊 Дневен прогрес:</span>
        <span><strong><?= $totalCaloriesToday ?></strong> / <?= $dailyGoal ?> kcal (<?= round($progress) ?>%)</span>
      </div>
      <div class="progress-bar-container">
        <div class="progress-bar-fill" style="width: <?= $progress ?>%; background: <?= $progressColor ?>;">
          <?= round($progress) ?>%
        </div>
      </div>
      <?php if ($progress > 100): ?>
        <p style="color: #f44336; margin-top: 10px; font-size: 14px;">⚠️ Надвишена дневна норма!</p>
      <?php elseif ($progress > 80): ?>
        <p style="color: #ff9800; margin-top: 10px; font-size: 14px;">⚡ Близо до целта!</p>
      <?php else: ?>
        <p style="color: #4caf50; margin-top: 10px; font-size: 14px;">✓ Добър прогрес!</p>
      <?php endif; ?>
    </div>

    <!-- Графика и съвети -->
    <div class="content-layout">
      <div class="content-box">
        <h3>📈 График калории</h3>
        <div class="chart-container">
          <canvas id="calorieChart"></canvas>
        </div>
        <div style="text-align: center; margin-top: 15px;">
          <a href="export.php" class="btn btn-secondary">📊 Изтегли данните (CSV)</a>
        </div>
      </div>

      <div class="content-box">
        <h3>💡 Съвети за здравословен живот</h3>
        <div class="accordion">
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

    <!-- Препоръчани ястия -->
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

    <!-- Отзиви -->
    <div class="testimonials">
      <h3>💬 Отзиви от потребители</h3>
      <div class="testimonial">
        <p>„Много ми помага да следя калориите си – интерфейсът е супер лесен!"</p>
        <span>— Мария, София</span>
      </div>
      <div class="testimonial">
        <p>„Благодарение на този тракер свалих 5 кг за месец!"</p>
        <span>— Иван, Пловдив</span>
      </div>
      <div class="testimonial">
        <p>„Обичам съветите и леките рецепти – точно каквото ми трябваше."</p>
        <span>— Елена, Варна</span>
      </div>
    </div>
  </div>

  <footer class="footer">
    <p>&copy; 2025 Калории Тракер | Всички права запазени</p>
  </footer>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const accordionHeaders = document.querySelectorAll('.accordion-header');
  accordionHeaders.forEach(header => {
    header.addEventListener('click', function(e) {
      e.preventDefault();

      // Close other open items within the same accordion container
      const parent = header.closest('.accordion');
      if (parent) {
        parent.querySelectorAll('.accordion-header.active').forEach(other => {
          if (other !== header) {
            other.classList.remove('active');
            const otherContent = other.nextElementSibling;
            if (otherContent && otherContent.classList.contains('accordion-content')) {
              otherContent.classList.remove('open');
            }
          }
        });
      }

      // Toggle current
      header.classList.toggle('active');
      const content = header.nextElementSibling;
      if (content && content.classList.contains('accordion-content')) {
        content.classList.toggle('open');
      }
    });
  });
});
</script>





</body>
</html>
