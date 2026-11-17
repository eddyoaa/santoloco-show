<?php
$month = input('month');
$month = valid_month($month) ? $month : date('Y-m');
$from = "$month-01";
$to = date('Y-m-d', strtotime("$from +1 month"));
$userId = (int) input('user');

$people = all('SELECT * FROM users ORDER BY active DESC, name');
$rows = [];
foreach ($people as $p) {
    $id = (int) $p['id'];
    $sessions = sessions_for($id, $from, $to);
    $worked = array_sum(array_map(fn($s) => (int) $s['minutes'], $sessions));
    $planned = planned_minutes($id, $from, $to);
    if (!$p['active'] && !$worked && !$planned) {
        continue;
    }
    $att = attendance_summary($id, $from, $to);
    $plannedToDate = planned_minutes_to_date($id, $from, $to);
    $rows[] = [
        'person' => $p,
        'worked' => $worked,
        'planned' => $planned,
        'plannedToDate' => $plannedToDate,
        'diff' => $worked - $plannedToDate,
        'days' => count(array_unique(array_map(fn($s) => substr($s['ref'], 0, 10), array_filter($sessions, fn($s) => $s['minutes'])))),
        'issues' => count(array_filter($sessions, fn($s) => in_array($s['status'], ['offen', 'ohne_kommen'], true))),
        'att' => $att,
        'sessions' => $sessions,
    ];
}

if (input('export') === 'csv') {
    csv_download(
        "auswertung-$month.csv",
        ['Mitarbeiter', 'Geplant gesamt (h)', 'Geplant bis heute (h)', 'Gearbeitet (h)', 'Plus/Minus (h)', 'Arbeitstage', 'Schichten', 'Verspätungen', 'Nicht erschienen', 'Fehlende Stempel'],
        array_map(fn($r) => [
            $r['person']['name'], fmt_hours_dec($r['planned']), fmt_hours_dec($r['plannedToDate']), fmt_hours_dec($r['worked']), fmt_hours_dec($r['diff']),
            $r['days'], $r['att']['shifts'], $r['att']['late'], $r['att']['missing'], $r['issues'],
        ], $rows)
    );
}

$detail = null;
if ($userId) {
    foreach ($rows as $r) {
        if ((int) $r['person']['id'] === $userId) {
            $detail = $r;
        }
    }
}

// Wochenverlauf des Teams im Monat
$weeks = [];
for ($w = monday($from); $w < $to; $w = add_days($w, 7)) {
    $wFrom = max($w, $from);
    $wTo = min(add_days($w, 7), $to);
    $worked = 0;
    $planned = 0;
    foreach ($rows as $r) {
        $id = (int) $r['person']['id'];
        $worked += array_sum(array_map(fn($s) => (int) $s['minutes'], array_filter($r['sessions'], fn($s) => $s['ref'] >= $wFrom && $s['ref'] < $wTo)));
        $planned += planned_minutes($id, $wFrom, $wTo);
    }
    $weeks[] = ['label' => 'KW ' . (int) date('W', ts($w)), 'worked' => $worked, 'planned' => $planned];
}

render('admin_reports', [
    'month' => $month,
    'rows' => $rows,
    'detail' => $detail,
    'weeks' => $weeks,
    'totals' => [
        'worked' => array_sum(array_column($rows, 'worked')),
        'planned' => array_sum(array_column($rows, 'planned')),
        'diff' => array_sum(array_column($rows, 'diff')),
        'late' => array_sum(array_map(fn($r) => $r['att']['late'], $rows)),
        'missing' => array_sum(array_map(fn($r) => $r['att']['missing'], $rows)),
        'issues' => array_sum(array_column($rows, 'issues')),
    ],
], 'Auswertungen');
