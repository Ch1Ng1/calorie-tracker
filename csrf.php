<?php
// CSRF Token Management

/**
 * Генерира и съхранява CSRF токен в сесията
 */
function generateCSRFToken() {
    // Провери дали токена съществува, ако не - генерирай нов
    if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Верифицира CSRF токена от форма
 */
function verifyCSRFToken($token) {
    // Провери дали имаме token в session
    if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Сравни с constant time comparison
    if (empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Печата скритото поле със CSRF токена за формите
 */
function getCsrfField() {
    $token = generateCSRFToken();
    return "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . "'>";
}
?>
?>
