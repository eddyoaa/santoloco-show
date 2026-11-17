<?php
$person = one('SELECT * FROM users WHERE id = ?', [(int) input('id')]);
if (!$person) {
    flash('Mitarbeiter nicht gefunden.', 'error');
    redirect('admin/staff');
}
$id = (int) $person['id'];
$isSelf = $id === (int) current_user()['id'];
$error = '';

if (is_post()) {
    $do = input('do');

    if ($do === 'save') {
        $name = input('name');
        $email = mb_strtolower(input('email'));
        $role = input('role') === 'admin' ? 'admin' : 'mitarbeiter';
        $active = input('active') === '1' ? 1 : 0;
        $hours = (float) str_replace(',', '.', input('weekly_hours', '0'));
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', input('color')) ? input('color') : $person['color'];
        $admins = (int) val("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1 AND id <> ?", [$id]);

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Bitte Name und gültige E-Mail angeben.';
        } elseif (val('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$email, $id])) {
            $error = 'Diese E-Mail wird schon verwendet.';
        } elseif ($person['role'] === 'admin' && ($role !== 'admin' || !$active) && $admins === 0) {
            $error = 'Es muss mindestens ein aktiver Admin bleiben.';
        } elseif ($isSelf && !$active) {
            $error = 'Du kannst dein eigenes Konto nicht deaktivieren.';
        } else {
            q(
                'UPDATE users SET name = ?, email = ?, role = ?, active = ?, weekly_hours = ?, color = ? WHERE id = ?',
                [$name, $email, $role, $active, $hours, $color, $id]
            );
            if (!$active) {
                q("UPDATE chips SET status = 'gesperrt' WHERE user_id = ?", [$id]);
            }
            audit('mitarbeiter_geaendert', $name . ($active ? '' : ' (deaktiviert, Chips gesperrt)'));
            flash('Gespeichert.');
            redirect('admin/staff_edit', ['id' => $id]);
        }
    }

    if ($do === 'password') {
        $pw = (string) ($_POST['password'] ?? '');
        if (strlen($pw) < 10) {
            $error = 'Das neue Passwort muss mindestens 10 Zeichen haben.';
        } else {
            q('UPDATE users SET password_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $id]);
            audit('passwort_zurueckgesetzt', $person['name']);
            flash("Neues Passwort für {$person['name']} gesetzt. Gib es persönlich weiter.");
            redirect('admin/staff_edit', ['id' => $id]);
        }
    }
}

$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-d', strtotime("$monthStart +1 month"));

render('admin_staff_edit', [
    'person' => $person,
    'isSelf' => $isSelf,
    'error' => $error,
    'chips' => all('SELECT * FROM chips WHERE user_id = ? ORDER BY status, created_at', [$id]),
    'status' => current_status($id),
    'monthWorked' => worked_minutes($id, $monthStart, $monthEnd),
    'monthPlanned' => planned_minutes($id, $monthStart, $monthEnd),
    'upcoming' => all('SELECT * FROM shifts WHERE user_id = ? AND end_at > ? ORDER BY start_at LIMIT 5', [$id, now_str()]),
], $person['name']);
