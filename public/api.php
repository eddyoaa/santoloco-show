<?php
declare(strict_types=1);

/*
 * Schnittstelle für das Stempelterminal (ESP32).
 *
 * POST /api.php
 *   Header:  X-Terminal-Key: <Schlüssel aus config.php>
 *   Body:    JSON {"uid": "04A31F7C", "action": "kommen" | "gehen" (optional)}
 *            oder Formularfelder uid=...&action=...
 * GET /api.php?ping=1   (mit Header) prüft Verbindung und liefert die Serverzeit
 *
 * Antwort (JSON): ok, status, message, detail?, name?, action?, time?
 */

require __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$key = (string) ($_SERVER['HTTP_X_TERMINAL_KEY'] ?? '');
$terminal = null;
foreach ((array) config('terminal_keys', []) as $name => $secret) {
    $secret = (string) $secret;
    if (strlen($secret) >= 16 && $secret !== 'BITTE-AENDERN' && hash_equals($secret, $key)) {
        $terminal = (string) $name;
        break;
    }
}
if ($terminal === null) {
    respond(['ok' => false, 'status' => 'unauthorized', 'message' => 'Terminal nicht berechtigt'], 401);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    respond(['ok' => true, 'status' => 'pong', 'terminal' => $terminal, 'time' => date('H:i:s'), 'date' => date('Y-m-d')]);
}

$body = json_decode((string) file_get_contents('php://input'), true);
$data = is_array($body) ? $body : $_POST;
$uid = (string) ($data['uid'] ?? '');
$action = isset($data['action']) && in_array($data['action'], ['kommen', 'gehen'], true) ? $data['action'] : null;

try {
    respond(terminal_book($uid, $action, $terminal));
} catch (Throwable $e) {
    error_log('Stempel-API: ' . $e->getMessage());
    respond(['ok' => false, 'status' => 'error', 'message' => 'Serverfehler'], 500);
}
