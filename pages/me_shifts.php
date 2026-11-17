<?php
$me = current_user();
$week = input('week');
$week = valid_date($week) ? monday($week) : monday(date('Y-m-d'));

$upcoming = all(
    'SELECT * FROM shifts WHERE user_id = ? AND end_at > ? AND start_at < ? ORDER BY start_at',
    [$me['id'], now_str(), add_days(date('Y-m-d'), 29)]
);

render('me_shifts', [
    'me' => $me,
    'week' => $week,
    'upcoming' => $upcoming,
    'people' => active_users(),
    'shifts' => shifts_for(null, $week, add_days($week, 7)),
], 'Schichten');
