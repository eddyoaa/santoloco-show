<?php
declare(strict_types=1);

/*
 * Arbeitszeit-Logik.
 * Eine "Sitzung" ist ein Paar aus Kommen und Gehen. Fehlt eine Hälfte, wird die Sitzung markiert
 * (offen = Ausstempeln vergessen, ohne_kommen = Gehen ohne vorheriges Kommen).
 */

const FLAG_LABELS = [
    'ausstempeln_vergessen' => 'Ausstempeln vergessen',
    'ohne_kommen' => 'Gehen ohne Kommen',
    'pruefen' => 'Bitte prüfen',
];

const SESSION_LABELS = [
    'ok' => 'OK',
    'laufend' => 'Läuft',
    'offen' => 'Ausstempeln fehlt',
    'ohne_kommen' => 'Kommen fehlt',
];

function stamps_for(int $userId, string $from, string $to): array
{
    return all(
        'SELECT * FROM stamps WHERE user_id = ? AND cancelled_at IS NULL AND stamped_at >= ? AND stamped_at < ? ORDER BY stamped_at, id',
        [$userId, $from, $to]
    );
}

/** Kommen/Gehen-Paare, deren Beginn (bzw. Ende, falls Kommen fehlt) in [from, to) liegt. */
function sessions_for(int $userId, string $from, string $to): array
{
    $stamps = stamps_for($userId, date('Y-m-d H:i:s', ts($from) - 86400), date('Y-m-d H:i:s', ts($to) + 86400));
    $limit = (int) config('forgotten_hours', 14) * 3600;
    $out = [];
    $open = null;
    foreach ($stamps as $s) {
        if ($s['type'] === 'kommen') {
            if ($open) {
                $out[] = session_row($open, null, 'offen');
            }
            $open = $s;
        } elseif ($open) {
            $out[] = session_row($open, $s, 'ok');
            $open = null;
        } else {
            $out[] = session_row(null, $s, 'ohne_kommen');
        }
    }
    if ($open) {
        $running = time() - ts($open['stamped_at']) < $limit;
        $out[] = session_row($open, null, $running ? 'laufend' : 'offen');
    }
    return array_values(array_filter($out, fn($x) => $x['ref'] >= $from && $x['ref'] < $to));
}

function session_row(?array $in, ?array $out, string $status): array
{
    $minutes = null;
    if ($in && $out) {
        $minutes = intdiv(ts($out['stamped_at']) - ts($in['stamped_at']), 60);
    } elseif ($in && $status === 'laufend') {
        $minutes = max(0, intdiv(time() - ts($in['stamped_at']), 60));
    }
    return [
        'start' => $in['stamped_at'] ?? null,
        'end' => $out['stamped_at'] ?? null,
        'in' => $in,
        'out' => $out,
        'minutes' => $minutes,
        'status' => $status,
        'ref' => $in['stamped_at'] ?? $out['stamped_at'],
    ];
}

function worked_minutes(int $userId, string $from, string $to): int
{
    return array_sum(array_map(fn($s) => (int) $s['minutes'], sessions_for($userId, $from, $to)));
}

function shifts_for(?int $userId, string $from, string $to): array
{
    $sql = 'SELECT s.*, u.name, u.color FROM shifts s JOIN users u ON u.id = s.user_id
            WHERE s.start_at < ? AND s.end_at > ?' . ($userId ? ' AND s.user_id = ?' : '') . ' ORDER BY s.start_at, u.name';
    return all($sql, $userId ? [$to, $from, $userId] : [$to, $from]);
}

function shift_minutes(array $shift, ?string $from = null, ?string $to = null): int
{
    $start = max(ts($shift['start_at']), $from ? ts($from) : 0);
    $end = min(ts($shift['end_at']), $to ? ts($to) : PHP_INT_MAX);
    return max(0, intdiv($end - $start, 60));
}

function planned_minutes(int $userId, string $from, string $to): int
{
    return array_sum(array_map(fn($s) => shift_minutes($s, $from, $to), shifts_for($userId, $from, $to)));
}

/** Geplante Minuten nur bis jetzt – für eine faire Differenz mitten in Woche oder Monat. */
function planned_minutes_to_date(int $userId, string $from, string $to): int
{
    $now = now_str();
    return $from >= $now ? 0 : planned_minutes($userId, $from, min($to, $now));
}

/** Aktueller Status: eingestempelt oder nicht. */
function current_status(int $userId): array
{
    $last = one('SELECT * FROM stamps WHERE user_id = ? AND cancelled_at IS NULL ORDER BY stamped_at DESC, id DESC LIMIT 1', [$userId]);
    $limit = (int) config('forgotten_hours', 14) * 3600;
    $present = $last && $last['type'] === 'kommen' && time() - ts($last['stamped_at']) < $limit;
    return ['present' => $present, 'since' => $present ? $last['stamped_at'] : null, 'last' => $last];
}

/** Erstes Kommen zu einer Schicht (frühestens 2 h vor Beginn) und ob es verspätet war. */
function shift_attendance(array $shift): array
{
    $first = one(
        "SELECT stamped_at FROM stamps WHERE user_id = ? AND type = 'kommen' AND cancelled_at IS NULL
         AND stamped_at >= ? AND stamped_at < ? ORDER BY stamped_at LIMIT 1",
        [$shift['user_id'], date('Y-m-d H:i:s', ts($shift['start_at']) - 7200), $shift['end_at']]
    );
    $grace = (int) config('late_grace_minutes', 5) * 60;
    $started = ts($shift['start_at']) <= time();
    if ($first) {
        $late = ts($first['stamped_at']) > ts($shift['start_at']) + $grace;
        return ['state' => $late ? 'verspaetet' : 'puenktlich', 'at' => $first['stamped_at']];
    }
    if (ts($shift['end_at']) < time()) {
        return ['state' => 'fehlt', 'at' => null];
    }
    if ($started && ts($shift['start_at']) + $grace < time()) {
        return ['state' => 'ueberfaellig', 'at' => null];
    }
    return ['state' => 'geplant', 'at' => null];
}

function attendance_summary(int $userId, string $from, string $to): array
{
    $r = ['shifts' => 0, 'late' => 0, 'missing' => 0];
    foreach (shifts_for($userId, $from, $to) as $s) {
        if (ts($s['start_at']) > time()) {
            continue;
        }
        $r['shifts']++;
        $a = shift_attendance($s);
        if ($a['state'] === 'verspaetet') {
            $r['late']++;
        } elseif ($a['state'] === 'fehlt') {
            $r['missing']++;
        }
    }
    return $r;
}

function open_issues_count(): int
{
    return (int) val("SELECT COUNT(*) FROM stamps WHERE flag IS NOT NULL AND cancelled_at IS NULL")
        + (int) val("SELECT COUNT(*) FROM chips WHERE user_id IS NULL AND status = 'aktiv'");
}

function normalize_uid(string $raw): string
{
    return strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $raw) ?? '');
}

/**
 * Buchung vom Terminal.
 * $action: null = automatisch (schlauer Wechsel), sonst 'kommen' oder 'gehen' (z. B. Kippschalter).
 */
function terminal_book(string $rawUid, ?string $action = null, string $terminal = 'terminal'): array
{
    $uid = normalize_uid($rawUid);
    if ($uid === '' || strlen($uid) > 32) {
        return ['ok' => false, 'status' => 'invalid', 'message' => 'Chip nicht lesbar'];
    }

    $chip = one('SELECT * FROM chips WHERE uid = ?', [$uid]);
    if (!$chip) {
        insert('chips', ['uid' => $uid, 'status' => 'aktiv', 'last_seen_at' => now_str(), 'created_at' => now_str()]);
        q('INSERT INTO audit_log (actor_id, action, details, created_at) VALUES (NULL, ?, ?, ?)', ['chip_erkannt', "Neuer Chip $uid am Terminal $terminal", now_str()]);
        return ['ok' => false, 'status' => 'unknown', 'message' => 'Neuer Chip', 'detail' => 'Bitte im Admin-Bereich zuordnen', 'uid' => $uid];
    }
    q('UPDATE chips SET last_seen_at = ? WHERE id = ?', [now_str(), $chip['id']]);

    if ($chip['status'] !== 'aktiv') {
        return ['ok' => false, 'status' => 'blocked', 'message' => 'Chip gesperrt'];
    }
    if (!$chip['user_id']) {
        return ['ok' => false, 'status' => 'unassigned', 'message' => 'Chip nicht zugeordnet', 'detail' => 'Bitte im Admin-Bereich zuordnen', 'uid' => $uid];
    }
    $user = one('SELECT * FROM users WHERE id = ? AND active = 1', [$chip['user_id']]);
    if (!$user) {
        return ['ok' => false, 'status' => 'inactive', 'message' => 'Konto deaktiviert'];
    }

    $last = one('SELECT * FROM stamps WHERE user_id = ? AND cancelled_at IS NULL ORDER BY stamped_at DESC, id DESC LIMIT 1', [$user['id']]);
    $now = time();
    $name = first_name($user['name']);

    // Doppeltes Antippen ignorieren
    if ($last && $now - ts($last['stamped_at']) < (int) config('cooldown_seconds', 120)) {
        return [
            'ok' => true, 'status' => 'ignored', 'name' => $name, 'action' => $last['type'],
            'message' => $last['type'] === 'kommen' ? 'Bereits eingestempelt' : 'Bereits ausgestempelt',
            'time' => fmt_time($last['stamped_at']),
        ];
    }

    $open = $last && $last['type'] === 'kommen';
    $stale = $open && $now - ts($last['stamped_at']) >= (int) config('forgotten_hours', 14) * 3600;
    $type = in_array($action, ['kommen', 'gehen'], true) ? $action : (($open && !$stale) ? 'gehen' : 'kommen');

    $flag = null;
    if ($type === 'kommen' && $open) {
        q('UPDATE stamps SET flag = ? WHERE id = ?', ['ausstempeln_vergessen', $last['id']]);
    } elseif ($type === 'gehen' && !$open) {
        $flag = 'ohne_kommen';
    } elseif ($type === 'gehen' && $stale) {
        $flag = 'pruefen';
    }

    insert('stamps', [
        'user_id' => $user['id'],
        'chip_id' => $chip['id'],
        'type' => $type,
        'stamped_at' => date('Y-m-d H:i:s', $now),
        'source' => 'terminal',
        'flag' => $flag,
        'note' => $terminal !== 'terminal' ? "Terminal: $terminal" : null,
        'created_at' => now_str(),
    ]);

    return [
        'ok' => true, 'status' => 'booked', 'name' => $name, 'action' => $type,
        'message' => $type === 'kommen' ? 'Eingestempelt' : 'Ausgestempelt',
        'time' => date('H:i', $now),
    ];
}
