<?php
// Configuración de Seguridad para Hostinger
// Incluir este archivo en páginas sensibles

// Prevenir acceso directo
if (!defined('SECURITY_CONFIG')) {
    define('SECURITY_CONFIG', true);
}

// Configuración de sesiones seguras
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Configuración de errores para producción
if ($_SERVER['HTTP_HOST'] !== 'localhost' && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'error.log');
}

// Función para sanitizar entrada
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para validar email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para generar token CSRF
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Función para limpiar logs antiguos
function clean_old_logs() {
    $log_file = 'error.log';
    if (file_exists($log_file) && filesize($log_file) > 10485760) { // 10MB
        file_put_contents($log_file, '');
    }
}

// Función para registrar actividad
function log_activity($user, $action, $details = '') {
    $log_entry = date('Y-m-d H:i:s') . " - User: {$user} - Action: {$action} - Details: {$details}\n";
    file_put_contents('activity.log', $log_entry, FILE_APPEND | LOCK_EX);
}

// Configuración de límites de intentos de login
function check_login_attempts($ip) {
    $attempts_file = 'login_attempts.json';
    $max_attempts = 5;
    $lockout_time = 900; // 15 minutos
    
    if (!file_exists($attempts_file)) {
        file_put_contents($attempts_file, '{}');
    }
    
    $attempts = json_decode(file_get_contents($attempts_file), true);
    
    if (isset($attempts[$ip])) {
        if ($attempts[$ip]['count'] >= $max_attempts) {
            if (time() - $attempts[$ip]['last_attempt'] < $lockout_time) {
                return false; // Bloqueado
            } else {
                unset($attempts[$ip]); // Reset después del tiempo
            }
        }
    }
    
    return true; // Permitir login
}

// Función para registrar intento de login fallido
function record_failed_login($ip) {
    $attempts_file = 'login_attempts.json';
    
    if (!file_exists($attempts_file)) {
        file_put_contents($attempts_file, '{}');
    }
    
    $attempts = json_decode(file_get_contents($attempts_file), true);
    
    if (!isset($attempts[$ip])) {
        $attempts[$ip] = ['count' => 0, 'last_attempt' => 0];
    }
    
    $attempts[$ip]['count']++;
    $attempts[$ip]['last_attempt'] = time();
    
    file_put_contents($attempts_file, json_encode($attempts));
}

// Función para limpiar intentos de login antiguos
function clean_old_login_attempts() {
    $attempts_file = 'login_attempts.json';
    $lockout_time = 900; // 15 minutos
    
    if (file_exists($attempts_file)) {
        $attempts = json_decode(file_get_contents($attempts_file), true);
        $current_time = time();
        
        foreach ($attempts as $ip => $data) {
            if ($current_time - $data['last_attempt'] > $lockout_time) {
                unset($attempts[$ip]);
            }
        }
        
        file_put_contents($attempts_file, json_encode($attempts));
    }
}

// Ejecutar limpieza automática
clean_old_logs();
clean_old_login_attempts();
?>
