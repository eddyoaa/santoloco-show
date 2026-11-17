<?php
$people = all('SELECT * FROM users ORDER BY active DESC, name');
$userId = (int) input('user');
$flagged = input('flagged') === '1';
$from = input('from');
$to = input('to');
if (!valid_date($from)) {
    $from = $flagged ? date('Y-m-d', strtotime('-60 days')) : monday(date('Y-m-d'));
}
if (!valid_date($to)) {
    $to = $flagged ? date('Y-m-d') : add_days(monday(date('Y-m-d')), 6);
}
$filter = array_filter(['user' => $userId ?: null, 'from' => $from, 'to' => $to, 'flagged' => $flagged ? 1 : null]);
$error = '';

if (is_post()) {
    $do = input('do');

    if ($do === 'add') {
        $target = one('SELECT * FROM users WHERE id = ?', [(int) input('user_id')]);
        $type = input('type');
        $date = input('date');
        $time = input('time');
        if (!$target || !in_array($type, ['kommen', 'gehen'], true) || !valid_date($date) || !valid_time($time)) {
            $error = 'Bitte Mitarbeiter, Art, Datum und Uhrzeit angeben.';
        } elseif (ts("$date $time:00") > time() + 300) {
            $error = 'Buchungen in der Zukunft sind nicht möglich.';
        } else {
            $at = "$date $time:00";
            insert('stamps', [
                'user_id' => $target['id'], 'type' => $type, 'stamped_at' => $at, 'source' => 'admin',
                'note' => mb_substr(input('note'), 0, 255) ?: null, 'created_at' => now_str(),
            ]);
            // Ein nachgetragenes Gehen erledigt ein "Ausstempeln vergessen" am selben Tag
            if ($type === 'gehen') {
                q(
                    "UPDATE stamps SET flag = NULL WHERE user_id = ? AND flag = 'ausstempeln_vergessen' AND type = 'kommen' AND stamped_at < ? AND stamped_at >= ?",
                    [$target['id'], $at, date('Y-m-d H:i:s', ts($at) - 86400)]
                );
            }
            audit('zeit_nachgetragen', "{$target['name']}: " . ($type === 'kommen' ? 'Kommen' : 'Gehen') . ' ' . fmt_dt($at));
            flash("Buchung für {$target['name']} nachgetragen.");
            redirect('admin/stamps', $filter);
        }
    }

    $stamp = in_array($do, ['edit', 'cancel', 'restore', 'resolve'], true)
        ? one('SELECT s.*, u.name FROM stamps s JOIN users u ON u.id = s.user_id WHERE s.id = ?', [(int) input('id')])
        : null;
    if ($stamp) {
        $label = "{$stamp['name']}: " . ($stamp['type'] === 'kommen' ? 'Kommen' : 'Gehen') . ' ' . fmt_dt($stamp['stamped_at']);
        if ($do === 'edit') {
            $dt = str_replace('T', ' ', input('stamped_at'));
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dt) || !valid_date(substr($dt, 0, 10))) {
                flash('Ungültige Zeit.', 'error');
            } elseif (ts("$dt:00") > time() + 300) {
                flash('Buchungen in der Zukunft sind nicht möglich.', 'error');
            } else {
                q('UPDATE stamps SET stamped_at = ?, source = ? WHERE id = ?', ["$dt:00", 'admin', $stamp['id']]);
                audit('zeit_korrigiert', "$label → " . fmt_dt("$dt:00"));
                flash('Zeit korrigiert.');
            }
        } elseif ($do === 'cancel') {
            q('UPDATE stamps SET cancelled_at = ? WHERE id = ?', [now_str(), $stamp['id']]);
            audit('zeit_storniert', $label);
            flash('Buchung storniert.');
        } elseif ($do === 'restore') {
            q('UPDATE stamps SET cancelled_at = NULL WHERE id = ?', [$stamp['id']]);
            audit('zeit_wiederhergestellt', $label);
            flash('Buchung wiederhergestellt.');
        } elseif ($do === 'resolve') {
            q('UPDATE stamps SET flag = NULL WHERE id = ?', [$stamp['id']]);
            audit('hinweis_erledigt', $label);
            flash('Als erledigt markiert.');
        }
        redirect('admin/stamps', $filter);
    }
}

$where = ['s.stamped_at >= ?', 's.stamped_at < ?'];
$params = [$from, add_days($to, 1)];
if ($userId) {
    $where[] = 's.user_id = ?';
    $params[] = $userId;
}
if ($flagged) {
    $where[] = 's.flag IS NOT NULL AND s.cancelled_at IS NULL';
}
$stamps = all(
    'SELECT s.*, u.name, u.color, c.uid AS chip_uid FROM stamps s JOIN users u ON u.id = s.user_id LEFT JOIN chips c ON c.id = s.chip_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY s.stamped_at DESC, s.id DESC LIMIT 500',
    $params
);

if (input('export') === 'csv') {
    $rows = [];
    foreach ($userId ? array_filter($people, fn($p) => (int) $p['id'] === $userId) : $people as $p) {
        foreach (sessions_for((int) $p['id'], $from, add_days($to, 1)) as $s) {
            $rows[] = [$p['name'], fmt_date($s['ref']), fmt_time($s['start']), fmt_time($s['end']),
                $s['minutes'] !== null ? fmt_hours_dec($s['minutes']) : '', SESSION_LABELS[$s['status']]];
        }
    }
    usort($rows, fn($a, $b) => [$a[0], strtotime(str_replace('.', '-', $a[1]))] <=> [$b[0], strtotime(str_replace('.', '-', $b[1]))]);
    csv_download("arbeitszeiten-$from-bis-$to.csv", ['Mitarbeiter', 'Datum', 'Kommen', 'Gehen', 'Dauer (h)', 'Status'], $rows);
}

render('admin_stamps', [
    'people' => $people,
    'stamps' => $stamps,
    'userId' => $userId,
    'from' => $from,
    'to' => $to,
    'flagged' => $flagged,
    'filter' => $filter,
    'error' => $error,
], 'Zeiten');
