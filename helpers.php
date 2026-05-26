<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config.php';


// -----------------------------------------------
// Output escaping
// -----------------------------------------------
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// -----------------------------------------------
// Auth helpers
// -----------------------------------------------
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function require_auth(): void
{
    if (!is_logged_in()) {
        header('Location: auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// -----------------------------------------------
// CSRF
// -----------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

// -----------------------------------------------
// Flash messages
// -----------------------------------------------
function flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

// -----------------------------------------------
// Validation
// -----------------------------------------------
function validate_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && strlen($email) <= 254;
}

function validate_password(string $pass): array
{
    $errors = [];
    if (strlen($pass) < 8)      $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $pass)) $errors[] = 'Password must contain an uppercase letter.';
    if (!preg_match('/[0-9]/', $pass)) $errors[] = 'Password must contain a number.';
    return $errors;
}

function validate_username(string $u): array
{
    $errors = [];
    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $u)) {
        $errors[] = 'Username must be 3–50 characters: letters, numbers, underscores only.';
    }
    return $errors;
}

// -----------------------------------------------
// File upload
// -----------------------------------------------
function handle_upload(array $file): array
{
    // $file is one element from $_FILES

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msgs = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
            UPLOAD_ERR_EXTENSION  => 'Upload stopped by extension.',
        ];
        throw new RuntimeException($msgs[$file['error']] ?? 'Unknown upload error.');
    }

    // Size check
    if ($file['size'] > MAX_FILE_BYTES) {
        throw new RuntimeException('File exceeds the 10 MB limit.');
    }

    // MIME type verification via finfo (not just client-supplied type)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!isset(ALLOWED_MIME_TYPES[$mimeType])) {
        throw new RuntimeException('Only PDF, PNG, and JPEG files are allowed.');
    }

    // Build safe file path
    $ext      = ALLOWED_MIME_TYPES[$mimeType];
    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir  = UPLOAD_DIR . date('Y/m/');

    if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
        throw new RuntimeException('Could not create upload directory.');
    }

    $destPath = $destDir . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    // Return relative path (relative to UPLOAD_DIR)
    return [
        'relative_path' => date('Y/m/') . $safeName,
        'mime_type'     => $mimeType,
        'file_type'     => $ext,
        'original_name' => basename($file['name']),
        'file_size'     => $file['size'],
    ];
}

// -----------------------------------------------
// Misc utilities
// -----------------------------------------------
function format_file_size(int $bytes): string
{
    if ($bytes >= 1_048_576) return round($bytes / 1_048_576, 1) . ' MB';
    if ($bytes >= 1_024)     return round($bytes / 1_024, 0)     . ' KB';
    return $bytes . ' B';
}

function avatar_initials(string $name): string
{
    $parts  = explode(' ', trim($name));
    $first  = strtoupper(mb_substr($parts[0], 0, 1));
    $second = isset($parts[1]) ? strtoupper(mb_substr($parts[1], 0, 1)) : '';
    return $first . $second;
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60)   . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600)  . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}