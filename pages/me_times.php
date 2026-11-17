<?php
$me = current_user();
$month = input('month');
$month = valid_month($month) ? $month : date('Y-m');
$from = "$month-01";
$to = date('Y-m-d', strtotime("$from +1 month"));

$sessions = sessions_for((int) $me['id'], $from, $to);

if (input('export') === 'csv') {
    csv_download(
        "zeiten-$month.csv",
        ['Datum', 'Kommen', 'Gehen', 'Dauer (h)', 'Status'],
        array_map(fn($s) => [
            fmt_date($s['ref']), fmt_time($s['start']), fmt_time($s['end']),
            $s['minutes'] !== null ? fmt_hours_dec($s['minutes']) : '', SESSION_LABELS[$s['status']],
        ], $sessions)
    );
}

render('me_times', [
    'month' => $month,
    'sessions' => $sessions,
    'worked' => array_sum(array_map(fn($s) => (int) $s['minutes'], $sessions)),
    'planned' => planned_minutes((int) $me['id'], $from, $to),
    'plannedToDate' => planned_minutes_to_date((int) $me['id'], $from, $to),
    'att' => attendance_summary((int) $me['id'], $from, $to),
], 'Meine Zeiten');
