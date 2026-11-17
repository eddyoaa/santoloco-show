<?php
declare(strict_types=1);

/*
 * Einmalige Einrichtung im Browser: legt die Tabellen an und erstellt den ersten Admin.
 * Funktioniert nur, solange es noch keinen Admin gibt. Danach diese Datei löschen.
 */

require __DIR__ . '/../src/bootstrap.php';
require APP_ROOT . '/src/schema.php';
start_session();

install_schema();
$done = (int) val("SELECT COUNT(*) FROM users WHERE role = 'admin'") > 0;
$error = '';

if (!$done && is_post()) {
    csrf_check();
    $name = input('name');
    $email = input('email');
    $pw = (string) ($_POST['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte Name und gültige E-Mail angeben.';
    } elseif (strlen($pw) < 10) {
        $error = 'Das Passwort muss mindestens 10 Zeichen haben.';
    } else {
        create_user($name, $email, $pw, 'admin', 0);
        $done = true;
        $created = true;
    }
}
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Einrichtung · <?= e(config('app_name', 'Zeiterfassung')) ?></title>
<link rel="stylesheet" href="assets/app.css">
</head>
<body class="auth">
<main class="auth-card">
  <div class="brand">SANTO<span>LOCO</span></div>
  <h1>Einrichtung</h1>
  <?php if ($done): ?>
    <?php if (!empty($created)): ?>
      <p class="alert ok">Admin-Konto angelegt.</p>
    <?php else: ?>
      <p class="alert">Die Einrichtung ist bereits abgeschlossen.</p>
    <?php endif; ?>
    <p>Bitte lösche jetzt die Datei <code>public/install.php</code> vom Server.</p>
    <a class="btn primary" href="index.php?p=login">Zur Anmeldung</a>
  <?php else: ?>
    <p class="muted">Lege das erste Admin-Konto an. Die Datenbanktabellen wurden bereits erstellt.</p>
    <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stack">
      <?= csrf_field() ?>
      <label>Name<input name="name" required value="<?= e(input('name')) ?>"></label>
      <label>E-Mail<input type="email" name="email" required value="<?= e(input('email')) ?>"></label>
      <label>Passwort (mind. 10 Zeichen)<input type="password" name="password" required minlength="10"></label>
      <button class="btn primary">Admin anlegen</button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
