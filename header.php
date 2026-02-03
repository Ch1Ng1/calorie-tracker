<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<style>
  /* Responsive Navbar */
  .navbar {
    background: #00796b;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: row;
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    gap: 20px;
    text-align: center;
  }

  .navbar-brand {
    font-size: 20px;
    font-weight: bold;
    color: white;
    text-decoration: none;
  }

  .navbar-toggle {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
  }

  .navbar-menu {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
  }

  .navbar-menu a {
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 5px;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 600;
    background-color: rgba(0, 0, 0, 0.2);
    display: inline-block;
  }

  .navbar-menu a:hover {
    background-color: rgba(0, 0, 0, 0.4);
    transform: translateY(-2px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  }

  .navbar-menu a[href*="settings"] {
    background-color: #ff9800;
  }

  .navbar-menu a[href*="settings"]:hover {
    background-color: #f57c00;
  }

  .navbar-menu a[href*="logout"] {
    background-color: #d32f2f;
  }

  .navbar-menu a[href*="logout"]:hover {
    background-color: #c62828;
  }

  @media (max-width: 767px) {
    .navbar {
      position: relative;
      flex-direction: column;
    }

    .navbar-toggle {
      display: block;
      position: absolute;
      right: 20px;
      top: 15px;
    }

    .navbar-brand {
      width: 100%;
      text-align: center;
    }

    .navbar-menu {
      position: absolute;
      top: 55px;
      left: 0;
      right: 0;
      flex-direction: column;
      background: #00796b;
      padding: 15px;
      gap: 10px;
      border-bottom: 1px solid rgba(255,255,255,0.2);
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      width: 100%;
      justify-content: center;
    }

    .navbar-menu.active {
      max-height: 500px;
    }

    .navbar-menu a {
      width: 100%;
      text-align: center;
      padding: 12px 10px;
    }

    .navbar h2 {
      width: 100%;
      font-size: 16px;
      margin: 10px 0 0 0;
      text-align: center;
    }

    .form-inline {
      width: 100%;
    }
  }

  .form-inline {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    justify-content: center;
  }

  .form-inline input {
    padding: 8px 10px;
    border: none;
    border-radius: 4px;
    font-size: 13px;
  }

  .form-inline input[type="date"] {
    width: auto;
  }

  .form-inline input[type="text"] {
    min-width: 100px;
    flex: 1;
  }

  .form-inline input[type="number"] {
    min-width: 80px;
    width: 100px;
  }

  .form-inline input[type="submit"] {
    background: #004d0cff;
    color: white;
    cursor: pointer;
    padding: 8px 15px;
    font-weight: bold;
    transition: background-color 0.3s;
  }

  .form-inline input[type="submit"]:hover {
    background-color: #003a09;
  }

  .logout-btn {
    background: #28c689ff;
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
    white-space: nowrap;
  }

  .logout-btn:hover {
    background: #b71c1c;
    transform: translateY(-2px);
  }

  @media (max-width: 479px) {
    .navbar {
      padding: 12px 15px;
    }

    .navbar-brand {
      font-size: 16px;
    }

    .form-inline {
      width: 100%;
      flex-direction: column;
    }

    .form-inline input,
    .logout-btn {
      width: 100%;
      padding: 10px;
      font-size: 13px;
    }

    .logout-btn {
      margin-left: 0;
      margin-top: 8px;
      text-align: center;
    }
  }
</style>

<div class="navbar">
  <a class="navbar-brand" href="index.php">📊 Калории Тракер</a>
  <button class="navbar-toggle" id="menuToggle">☰</button>
  <div class="navbar-menu" id="navbarMenu">
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="index.php">🏠 Начало</a>
      <a href="add.php">➕ Добави</a>
      <a href="settings.php">⚙️ Настройки</a>
      <a href="logout.php">🚪 Изход</a>
    <?php else: ?>
      <a href="login.php">🔑 Вход</a>
      <a href="register.php">📝 Регистрация</a>
    <?php endif; ?>
  </div>
</div>

<script>
  document.getElementById('menuToggle')?.addEventListener('click', function() {
    const menu = document.getElementById('navbarMenu');
    menu.classList.toggle('active');
  });

  // Затваряне на меню при клик на линк
  document.querySelectorAll('#navbarMenu a').forEach(link => {
    link.addEventListener('click', function() {
      document.getElementById('navbarMenu').classList.remove('active');
    });
  });
</script>
