<?php
// ============================================================
// config/database.php - Database Connection Configuration
// ============================================================

define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_NAME',     'parking_system');
define('DB_CHARSET',  'utf8mb4');

// App settings
define('APP_NAME',    'ParkSmart');
define('APP_URL',     'http://localhost/parking-system');
define('RATE_PER_HOUR', 50.00);   // PKR per hour
define('MIN_CHARGE',  20.00);     // Minimum charge in PKR

/**
 * Get PDO database connection (singleton)
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
        }
    }
    return $pdo;
}

/**
 * Calculate parking fee based on duration in minutes
 */
function calculateFee(int $minutes): float {
    $hours = $minutes / 60;
    $fee = $hours * RATE_PER_HOUR;
    return max(MIN_CHARGE, round($fee, 2));
}

/**
 * Sanitize output for HTML
 */
function esc(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format datetime for display
 */
function fmtDT(?string $dt): string {
    if (!$dt) return '—';
    return date('d M Y, h:i A', strtotime($dt));
}

/**
 * Format duration in minutes to human-readable
 */
function fmtDuration(int $minutes): string {
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h > 0) return "{$h}h {$m}m";
    return "{$m}m";
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Flash message helpers
 */
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/**
 * JSON response helper (for AJAX)
 */
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
