<?php
if (current_user()) {
    redirect('dashboard');
}
$error = '';
if (is_post()) {
    $result = attempt_login(input('email'), (string) ($_POST['password'] ?? ''));
    if ($result === true) {
        redirect('dashboard');
    }
    $error = $result;
}
render('login', ['error' => $error, 'email' => input('email')], 'Anmelden');
