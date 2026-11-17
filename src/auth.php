<?php
declare(strict_types=1);

function start_session(): void
{
    session_name('sl_session');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function current_user(): ?array
{
    static $cache = [];
    $id = $_SESSION['uid'] ?? null;
    if (!$id) {
        return null;
    }
    if (!array_key_exists($id, $cache)) {
        $cache[$id] = one('SELECT * FROM users WHERE id = ? AND active = 1', [$id]);
    }
    return $cache[$id];
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        render('error', ['message' => 'Diese Seite ist nur für Admins.'], 'Kein Zugriff');
        exit;
    }
}

/** @return true|string true on success, otherwise an error message */
function attempt_login(string $email, string $password): bool|string
{
    $u = one('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    $generic = 'E-Mail oder Passwort ist falsch.';
    if (!$u || !$u['active']) {
        password_verify($password, password_hash('dummy', PASSWORD_DEFAULT)); // ähnliche Laufzeit wie ein echter Versuch
        return $generic;
    }
    if ($u['locked_until'] && ts($u['locked_until']) > time()) {
        return 'Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.';
    }
    if (!password_verify($password, $u['password_hash'])) {
        $fails = (int) $u['failed_logins'] + 1;
        $lock = $fails >= 5 ? date('Y-m-d H:i:s', time() + 15 * 60) : null;
        q('UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?', [$lock ? 0 : $fails, $lock, $u['id']]);
        return $generic;
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    q('UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?', [$u['id']]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(400);
        exit('Sitzung abgelaufen. Bitte Seite neu laden und erneut versuchen.');
    }
}
