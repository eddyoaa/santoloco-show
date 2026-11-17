<?php
$week = input('week');
$week = valid_date($week) ? monday($week) : monday(date('Y-m-d'));
$people = active_users();
$error = '';

if (is_post()) {
    $do = input('do');

    if ($do === 'delete') {
        $s = one('SELECT s.*, u.name FROM shifts s JOIN users u ON u.id = s.user_id WHERE s.id = ?', [(int) input('id')]);
        if ($s) {
            q('DELETE FROM shifts WHERE id = ?', [$s['id']]);
            audit('schicht_geloescht', "{$s['name']}: " . fmt_dt($s['start_at']) . ' – ' . fmt_time($s['end_at']));
            flash('Schicht gelöscht.');
        }
        redirect('admin/shifts', ['week' => $week]);
    }

    if ($do === 'copy') {
        $prev = add_days($week, -7);
        $copied = 0;
        foreach (shifts_for(null, $prev, $week) as $s) {
            if (ts($s['start_at']) < ts($prev)) {
                continue;
            }
            $start = date('Y-m-d H:i:s', strtotime($s['start_at'] . ' +7 days'));
            $end = date('Y-m-d H:i:s', strtotime($s['end_at'] . ' +7 days'));
            $exists = val('SELECT COUNT(*) FROM shifts WHERE user_id = ? AND start_at = ? AND end_at = ?', [$s['user_id'], $start, $end]);
            if (!$exists) {
                insert('shifts', ['user_id' => $s['user_id'], 'start_at' => $start, 'end_at' => $end, 'note' => $s['note'], 'created_at' => now_str()]);
                $copied++;
            }
        }
        audit('schichten_kopiert', "$copied Schichten aus KW " . date('W', ts($prev)) . ' übernommen');
        flash($copied ? "$copied Schichten aus der Vorwoche übernommen." : 'Keine neuen Schichten zum Übernehmen gefunden.', $copied ? 'ok' : 'warn');
        redirect('admin/shifts', ['week' => $week]);
    }

    if ($do === 'save') {
        $id = (int) input('id');
        $userId = (int) input('user_id');
        $date = input('date');
        $from = input('from');
        $to = input('to');
        $note = mb_substr(input('note'), 0, 255);
        $person = one('SELECT * FROM users WHERE id = ? AND active = 1', [$userId]);

        if (!$person || !valid_date($date) || !valid_time($from) || !valid_time($to)) {
            $error = 'Bitte Mitarbeiter, Datum und Uhrzeiten vollständig angeben.';
        } else {
            $start = "$date $from:00";
            $end = "$date $to:00";
            if (ts($end) <= ts($start)) {
                $end = date('Y-m-d H:i:s', strtotime($end . ' +1 day')); // Schicht über Mitternacht
            }
            $overlap = val(
                'SELECT COUNT(*) FROM shifts WHERE user_id = ? AND start_at < ? AND end_at > ? AND id <> ?',
                [$userId, $end, $start, $id]
            );
            if ($overlap) {
                $error = "{$person['name']} hat in diesem Zeitraum schon eine Schicht.";
            } else {
                $data = ['user_id' => $userId, 'start_at' => $start, 'end_at' => $end, 'note' => $note ?: null];
                if ($id) {
                    q('UPDATE shifts SET user_id = ?, start_at = ?, end_at = ?, note = ? WHERE id = ?', [...array_values($data), $id]);
                    audit('schicht_geaendert', "{$person['name']}: " . fmt_dt($start) . ' – ' . fmt_time($end));
                    flash('Schicht gespeichert.');
                } else {
                    insert('shifts', $data + ['created_at' => now_str()]);
                    audit('schicht_angelegt', "{$person['name']}: " . fmt_dt($start) . ' – ' . fmt_time($end));
                    flash("Schicht für {$person['name']} angelegt.");
                }
                redirect('admin/shifts', ['week' => monday($date)]);
            }
        }
    }
}

// Formular vorbelegen
$edit = input('edit') ? one('SELECT * FROM shifts WHERE id = ?', [(int) input('edit')]) : null;
$form = [
    'id' => $edit['id'] ?? (is_post() ? (int) input('id') : 0),
    'user_id' => (int) ($edit['user_id'] ?? input('user_id', input('user'))),
    'date' => $edit ? date('Y-m-d', ts($edit['start_at'])) : (input('date') ?: (date('Y-m-d') >= $week && date('Y-m-d') < add_days($week, 7) ? date('Y-m-d') : $week)),
    'from' => $edit ? fmt_time($edit['start_at']) : (input('from') ?: '10:00'),
    'to' => $edit ? fmt_time($edit['end_at']) : (input('to') ?: '18:00'),
    'note' => $edit['note'] ?? input('note'),
];

$shifts = shifts_for(null, $week, add_days($week, 7));
render('admin_shifts', [
    'week' => $week,
    'people' => $people,
    'shifts' => $shifts,
    'form' => $form,
    'error' => $error,
    'total' => array_sum(array_map('shift_minutes', $shifts)),
], 'Schichtplan');
