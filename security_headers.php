<?php
/**
 * Security Headers Configuration
 * Всички security headers трябва да бъдат изпратени преди всеки output
 */

function setSecurityHeaders() {
    // Защита от clickjacking
    header("X-Frame-Options: SAMEORIGIN");
    
    // Защита от MIME type sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Защита от XSS атаки
    header("X-XSS-Protection: 1; mode=block");
    
    // Referrer Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Content Security Policy (базова)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com;");
    
    // Permissions Policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

// Вызов на функцията
setSecurityHeaders();
?>
