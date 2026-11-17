<?php
// Kopiere diese Datei nach config.php und passe die Werte an.

return [
    'app_name' => 'SantoLoco Zeiterfassung',
    'timezone' => 'Europe/Berlin',

    'db' => [
        // MySQL / MariaDB beim Webhoster:
        // 'dsn'  => 'mysql:host=localhost;dbname=santoloco;charset=utf8mb4',
        // 'user' => 'DB_BENUTZER',
        // 'pass' => 'DB_PASSWORT',

        // SQLite (zum Testen, keine Datenbank nötig):
        'dsn' => 'sqlite:' . __DIR__ . '/data/zeiterfassung.sqlite',
        'user' => null,
        'pass' => null,
    ],

    // Schlüssel für das Stempelterminal. Name => geheimer Schlüssel (mind. 32 zufällige Zeichen).
    // Erzeugen z. B. mit: php -r "echo bin2hex(random_bytes(24));"
    'terminal_keys' => [
        'eingang' => 'BITTE-AENDERN',
    ],

    // Zweites Antippen innerhalb dieser Zeit wird ignoriert (Sekunden)
    'cooldown_seconds' => 120,
    // Nach so vielen Stunden gilt ein offenes Kommen als "Ausstempeln vergessen"
    'forgotten_hours' => 14,
    // Kulanz, ab wann ein Kommen nach Schichtbeginn als verspätet zählt (Minuten)
    'late_grace_minutes' => 5,
];
