<?php
$me = current_user();
$error = '';
if (is_post()) {
    $current = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    $repeat = (string) ($_POST['repeat'] ?? '');
    if (!password_verify($current, $me['password_hash'])) {
        $error = 'Das aktuelle Passwort ist falsch.';
    } elseif (strlen($new) < 10) {
        $error = 'Das neue Passwort muss mindestens 10 Zeichen haben.';
    } elseif ($new !== $repeat) {
        $error = 'Die beiden neuen Passwörter stimmen nicht überein.';
    } else {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        audit('passwort_geaendert', $me['name']);
        flash('Passwort geändert.');
        redirect('me/profile');
    }
}
render('me_profile', ['me' => $me, 'error' => $error], 'Passwort');
