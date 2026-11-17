<?php
declare(strict_types=1);

const WEEKDAYS = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
const MONTHS = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
const USER_COLORS = ['#0e7c86', '#d9822b', '#5b6abf', '#2f9e5f', '#c2477a', '#8a6d3b', '#3a8fd1', '#9b59b6'];

function e(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function url(string $page, array $params = []): string
{
    return 'index.php?' . http_build_query(['p' => $page] + $params);
}

function redirect(string $page, array $params = []): never
{
    header('Location: ' . url($page, $params));
    exit;
}

function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function audit(string $action, string $details = ''): void
{
    q(
        'INSERT INTO audit_log (actor_id, action, details, created_at) VALUES (?, ?, ?, ?)',
        [current_user()['id'] ?? null, $action, $details, now_str()]
    );
}

function render(string $view, array $vars = [], string $title = ''): void
{
    $user = current_user();
    extract($vars);
    ob_start();
    require APP_ROOT . "/views/$view.php";
    $content = ob_get_clean();
    require APP_ROOT . '/views/layout.php';
}

function partial(string $view, array $vars = []): void
{
    extract($vars);
    require APP_ROOT . "/views/partials/$view.php";
}

function csv_download(string $filename, array $header, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM, damit Excel Umlaute richtig zeigt
    fputcsv($out, $header, ';');
    foreach ($rows as $r) {
        fputcsv($out, $r, ';');
    }
    fclose($out);
    exit;
}

/* ---------- Datum und Zeit ---------- */

function ts(string $dt): int
{
    return (int) strtotime($dt);
}

function wd(string $dt): string
{
    return WEEKDAYS[(int) date('N', ts($dt)) - 1];
}

function fmt_date(?string $dt): string
{
    return $dt ? date('d.m.Y', ts($dt)) : '–';
}

function fmt_day(string $dt): string
{
    return wd($dt) . ', ' . date('d.m.', ts($dt));
}

function fmt_time(?string $dt): string
{
    return $dt ? date('H:i', ts($dt)) : '–';
}

function fmt_dt(?string $dt): string
{
    return $dt ? date('d.m.Y H:i', ts($dt)) : '–';
}

function fmt_min(?int $m, bool $signed = false): string
{
    if ($m === null) {
        return '–';
    }
    $sign = $m < 0 ? '−' : ($signed && $m > 0 ? '+' : '');
    $m = abs($m);
    return $sign . intdiv($m, 60) . ':' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT) . ' h';
}

function fmt_hours_dec(int $m): string
{
    return number_format($m / 60, 2, ',', '');
}

function month_label(string $ym): string
{
    [$y, $m] = array_map('intval', explode('-', $ym));
    return MONTHS[$m - 1] . ' ' . $y;
}

function monday(string $date): string
{
    $t = ts($date);
    return date('Y-m-d', strtotime('-' . ((int) date('N', $t) - 1) . ' days', $t));
}

function add_days(string $date, int $days): string
{
    return date('Y-m-d', strtotime(sprintf('%+d days', $days), ts($date)));
}

function valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

function valid_time(string $s): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $s);
}

function valid_month(string $s): bool
{
    return (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $s);
}

function first_name(string $name): string
{
    return explode(' ', trim($name))[0];
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [''];
    $i = mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '');
    return mb_strtoupper($i);
}

function active_users(): array
{
    return all('SELECT * FROM users WHERE active = 1 ORDER BY name');
}
