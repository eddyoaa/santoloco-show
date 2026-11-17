<?php
require_once APP_ROOT . '/src/schema.php';
$error = '';

if (is_post() && input('do') === 'create') {
    $name = input('name');
    $email = mb_strtolower(input('email'));
    $pw = (string) ($_POST['password'] ?? '');
    $role = input('role') === 'admin' ? 'admin' : 'mitarbeiter';
    $hours = (float) str_replace(',', '.', input('weekly_hours', '0'));

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte Name und gültige E-Mail angeben.';
    } elseif (val('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) {
        $error = 'Diese E-Mail wird schon verwendet.';
    } elseif (strlen($pw) < 10) {
        $error = 'Das Startpasswort muss mindestens 10 Zeichen haben.';
    } elseif ($hours < 0 || $hours > 60) {
        $error = 'Bitte gültige Wochenstunden angeben (0–60).';
    } else {
        $count = (int) val('SELECT COUNT(*) FROM users');
        $id = create_user($name, $email, $pw, $role, $hours, USER_COLORS[$count % count(USER_COLORS)]);
        audit('mitarbeiter_angelegt', "$name ($email)");
        flash("$name angelegt. Gib das Startpasswort persönlich weiter.");
        redirect('admin/staff_edit', ['id' => $id]);
    }
}

$rows = all(
    "SELECT u.*, (SELECT COUNT(*) FROM chips c WHERE c.user_id = u.id AND c.status = 'aktiv') AS chip_count
     FROM users u ORDER BY u.active DESC, u.name"
);
foreach ($rows as &$r) {
    $r['status'] = $r['active'] ? current_status((int) $r['id']) : null;
}
unset($r);

render('admin_staff', ['rows' => $rows, 'error' => $error], 'Mitarbeiter');
