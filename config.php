<?php
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'studydrop');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',   'StudyDrop');
define('APP_URL',    getenv('APP_URL')    ?: 'http://localhost/studydrop');
// ✅ FIX 3: Use a project-relative path the web process can actually write to.
//    Set the UPLOAD_DIR env var in production to override (e.g. /srv/studydrop/uploads/).
define('UPLOAD_DIR', getenv('UPLOAD_DIR') ?: __DIR__ . '/uploads/');
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

// ✅ FIX 2: Added DOCX MIME type
define('ALLOWED_MIME_TYPES', [
    'application/pdf'      => 'pdf',
    'image/png'            => 'png',
    'image/jpeg'           => 'jpg',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'None',
        'cookie_secure'   => true,
        'use_strict_mode' => true,
    ]);
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}