<?php
$today = date('Y-m-d');
$week = monday($today);
$weekEnd = add_days($week, 7);
$people = active_users();

$present = [];
$weekRows = [];
foreach ($people as $p) {
    $st = current_status((int) $p['id']);
    if ($st['present']) {
        $present[] = ['person' => $p, 'since' => $st['since']];
    }
    $weekRows[] = [
        'person' => $p,
        'planned' => planned_minutes((int) $p['id'], $week, $weekEnd),
        'worked' => worked_minutes((int) $p['id'], $week, $weekEnd),
    ];
}
usort($present, fn($a, $b) => strcmp($a['since'], $b['since']));

$todayShifts = array_map(
    fn($s) => $s + ['att' => shift_attendance($s)],
    shifts_for(null, $today, add_days($today, 1))
);

$issues = [
    'chips' => (int) val("SELECT COUNT(*) FROM chips WHERE user_id IS NULL AND status = 'aktiv'"),
    'forgotten' => (int) val("SELECT COUNT(*) FROM stamps WHERE flag = 'ausstempeln_vergessen' AND cancelled_at IS NULL"),
    'other' => (int) val("SELECT COUNT(*) FROM stamps WHERE flag IN ('ohne_kommen', 'pruefen') AND cancelled_at IS NULL"),
];

render('admin_home', [
    'present' => $present,
    'todayShifts' => $todayShifts,
    'weekRows' => $weekRows,
    'weekPlanned' => array_sum(array_column($weekRows, 'planned')),
    'weekWorked' => array_sum(array_column($weekRows, 'worked')),
    'issues' => $issues,
    'staffCount' => count($people),
], 'Übersicht');
