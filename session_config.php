<?php
// Конфигурация на сесийните бисквитки за сигурност
session_set_cookie_params([
    'lifetime' => 0,           // Бисквитката изтича при затваряне на браузъра
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']), // Само HTTPS в production
    'httponly'  => true,       // JavaScript не може да достъпва бисквитката
    'samesite' => 'Strict'    // Защита от CSRF
]);

session_start();
