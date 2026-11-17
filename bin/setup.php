<?php
declare(strict_types=1);

/*
 * Einrichtung per Kommandozeile:
 *   php bin/setup.php "Vorname Nachname" chef@example.de "SicheresPasswort123"
 * Legt die Tabellen an und erstellt den ersten Admin.
 */

if (PHP_SAPI !== 'cli') {
    exit("Nur per Kommandozeile.\n");
}
require __DIR__ . '/../src/bootstrap.php';
require APP_ROOT . '/src/schema.php';

install_schema();
echo "Tabellen angelegt bzw. vorhanden.\n";

[, $name, $email, $password] = array_pad($argv, 4, null);
if (!$name || !$email || !$password) {
    echo "Kein Admin angelegt. Aufruf: php bin/setup.php \"Name\" email@example.de \"Passwort\"\n";
    exit(0);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
    fwrite(STDERR, "Bitte gültige E-Mail und ein Passwort mit mindestens 10 Zeichen angeben.\n");
    exit(1);
}
if (val('SELECT COUNT(*) FROM users WHERE email = ?', [mb_strtolower($email)])) {
    fwrite(STDERR, "Diese E-Mail existiert bereits.\n");
    exit(1);
}
create_user($name, $email, $password, 'admin', 0);
echo "Admin $email angelegt.\n";
