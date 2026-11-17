<?php
$me = current_user();
$uid = (int) $me['id'];
$today = date('Y-m-d');
$mon = monday($today);
$sun = add_days($mon, 7);
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-d', strtotime($monthStart . ' +1 month'));

$days = [];
for ($i = 0; $i < 7; $i++) {
    $d = add_days($mon, $i);
    $next = add_days($d, 1);
    $days[] = [
        'date' => $d,
        'worked' => worked_minutes($uid, $d, $next),
        'planned' => planned_minutes($uid, $d, $next),
    ];
}

$upcoming = all(
    'SELECT * FROM shifts WHERE user_id = ? AND end_at > ? ORDER BY start_at LIMIT 6',
    [$uid, now_str()]
);

render('me_dashboard', [
    'me' => $me,
    'status' => current_status($uid),
    'days' => $days,
    'weekWorked' => array_sum(array_column($days, 'worked')),
    'weekPlanned' => array_sum(array_column($days, 'planned')),
    'weekPlannedToDate' => planned_minutes_to_date($uid, $mon, $sun),
    'monthWorked' => worked_minutes($uid, $monthStart, $monthEnd),
    'monthPlanned' => planned_minutes($uid, $monthStart, $monthEnd),
    'monthAttendance' => attendance_summary($uid, $monthStart, $monthEnd),
    'upcoming' => $upcoming,
    'recent' => all('SELECT * FROM stamps WHERE user_id = ? AND cancelled_at IS NULL ORDER BY stamped_at DESC LIMIT 6', [$uid]),
    'chips' => (int) val("SELECT COUNT(*) FROM chips WHERE user_id = ? AND status = 'aktiv'", [$uid]),
], 'Übersicht');
