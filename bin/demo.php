<?php
declare(strict_types=1);

/*
 * Füllt die Datenbank mit Beispieldaten zum Ausprobieren.
 *   php bin/demo.php           (nur wenn noch keine Benutzer existieren)
 *   php bin/demo.php --reset   (löscht ALLE Daten und legt die Demo neu an)
 *
 * Anmeldung danach: chef@santoloco.test / demo-passwort  (Admin)
 *                   max@santoloco.test  / demo-passwort  (Mitarbeiter)
 */

if (PHP_SAPI !== 'cli') {
    exit("Nur per Kommandozeile.\n");
}
require __DIR__ . '/../src/bootstrap.php';
require APP_ROOT . '/src/schema.php';

install_schema();
if (in_array('--reset', $argv, true)) {
    foreach (['stamps', 'shifts', 'chips', 'audit_log', 'users'] as $t) {
        db()->exec("DELETE FROM $t");
    }
} elseif ((int) val('SELECT COUNT(*) FROM users') > 0) {
    fwrite(STDERR, "Es gibt schon Benutzer. Mit --reset werden alle Daten gelöscht und die Demo neu angelegt.\n");
    exit(1);
}

mt_srand(42);
$pw = 'demo-passwort';
$staff = [
    // Name, E-Mail, Rolle, Soll-Std., Farbe, Schichten [Wochentag 1=Mo => [von, bis]]
    ['Sandra Chef', 'chef@santoloco.test', 'admin', 0, '#14212b', []],
    ['Max Mustermann', 'max@santoloco.test', 'mitarbeiter', 30, '#0e7c86', [1 => ['10:00', '18:00'], 2 => ['10:00', '18:00'], 3 => ['10:00', '16:00'], 5 => ['10:00', '18:00']]],
    ['Lea Sommer', 'lea@santoloco.test', 'mitarbeiter', 20, '#d9822b', [4 => ['11:00', '18:00'], 5 => ['12:00', '18:00'], 6 => ['10:00', '17:00']]],
    ['Jonas Kraft', 'jonas@santoloco.test', 'mitarbeiter', 25, '#5b6abf', [1 => ['12:00', '18:00'], 3 => ['10:00', '18:00'], 4 => ['10:00', '16:00'], 6 => ['11:00', '18:00']]],
    ['Mia Wellen', 'mia@santoloco.test', 'mitarbeiter', 10, '#c2477a', [2 => ['14:00', '18:00'], 6 => ['10:00', '16:00']]],
];

$ids = [];
foreach ($staff as $i => [$name, $email, $role, $hours, $color]) {
    $ids[$i] = create_user($name, $email, $pw, $role, $hours, $color);
}
$chipUids = ['04A31F7C', '04B2C19A', '04C7E2D1', '04D8F310', '04E19A42'];
foreach ($ids as $i => $uid) {
    if ($i === 0) {
        continue;
    }
    insert('chips', ['uid' => $chipUids[$i - 1], 'user_id' => $uid, 'label' => 'Schlüsselanhänger', 'status' => 'aktiv', 'last_seen_at' => now_str(), 'created_at' => now_str()]);
}
insert('chips', ['uid' => '04F5A6B7', 'user_id' => null, 'status' => 'aktiv', 'last_seen_at' => date('Y-m-d H:i:s', time() - 3600), 'created_at' => now_str()]);

$startWeek = add_days(monday(date('Y-m-d')), -28);
$endWeek = add_days(monday(date('Y-m-d')), 14);
$forgottenDone = false;

for ($d = $startWeek; $d < $endWeek; $d = add_days($d, 1)) {
    $dow = (int) date('N', ts($d));
    foreach ($staff as $i => $row) {
        $plan = $row[5][$dow] ?? null;
        if (!$plan) {
            continue;
        }
        $start = "$d {$plan[0]}:00";
        $end = "$d {$plan[1]}:00";
        insert('shifts', ['user_id' => $ids[$i], 'start_at' => $start, 'end_at' => $end, 'note' => $dow === 6 ? 'Surfkurs-Tag' : null, 'created_at' => now_str()]);

        if (ts($start) - 600 > time()) {
            continue; // Zukunft
        }
        if (mt_rand(1, 40) === 1 && ts($end) < time() - 86400) {
            continue; // nicht erschienen
        }
        $late = mt_rand(1, 8) === 1;
        $in = ts($start) + ($late ? mt_rand(8, 25) : mt_rand(-12, 3)) * 60;
        if ($in > time()) {
            continue;
        }
        $chip = val('SELECT id FROM chips WHERE user_id = ?', [$ids[$i]]);
        insert('stamps', ['user_id' => $ids[$i], 'chip_id' => $chip, 'type' => 'kommen', 'stamped_at' => date('Y-m-d H:i:s', $in), 'source' => 'terminal', 'created_at' => now_str()]);

        $out = ts($end) + mt_rand(-5, 20) * 60;
        if ($out > time()) {
            continue; // läuft noch
        }
        // Einmal Ausstempeln vergessen (vorletzte Woche)
        if (!$forgottenDone && $i === 3 && ts($d) < ts(add_days(monday(date('Y-m-d')), -3)) && ts($d) > ts(add_days(monday(date('Y-m-d')), -14))) {
            q("UPDATE stamps SET flag = 'ausstempeln_vergessen' WHERE user_id = ? AND stamped_at = ?", [$ids[$i], date('Y-m-d H:i:s', $in)]);
            $forgottenDone = true;
            continue;
        }
        insert('stamps', ['user_id' => $ids[$i], 'chip_id' => $chip, 'type' => 'gehen', 'stamped_at' => date('Y-m-d H:i:s', $out), 'source' => 'terminal', 'created_at' => now_str()]);
    }
}

// "Heute" an die aktuelle Uhrzeit anpassen, damit die Übersicht Leben zeigt
$todayFrom = date('Y-m-d H:i:s', time() - 12 * 3600);
$todayTo = date('Y-m-d H:i:s', time() + 12 * 3600);
q('DELETE FROM stamps WHERE stamped_at >= ?', [$todayFrom]);
q('DELETE FROM shifts WHERE end_at > ? AND start_at < ?', [$todayFrom, $todayTo]);
$hour = (int) floor(time() / 3600) * 3600;
$todayPlan = [
    // Mitarbeiter-Index => [Beginn relativ zur vollen Stunde, Dauer, Kommen relativ zum Beginn in Minuten oder null]
    1 => [-3, 8, -6],     // Max: da, pünktlich
    2 => [-1, 6, 14],     // Lea: da, verspätet
    3 => [2, 6, null],    // Jonas: kommt später
    4 => [-4, 3, -2],     // Mia: schon wieder gegangen
];
foreach ($todayPlan as $i => [$startH, $dur, $inMin]) {
    $start = $hour + $startH * 3600;
    insert('shifts', ['user_id' => $ids[$i], 'start_at' => date('Y-m-d H:i:s', $start), 'end_at' => date('Y-m-d H:i:s', $start + $dur * 3600), 'note' => null, 'created_at' => now_str()]);
    if ($inMin !== null) {
        $chip = val('SELECT id FROM chips WHERE user_id = ?', [$ids[$i]]);
        insert('stamps', ['user_id' => $ids[$i], 'chip_id' => $chip, 'type' => 'kommen', 'stamped_at' => date('Y-m-d H:i:s', $start + $inMin * 60), 'source' => 'terminal', 'created_at' => now_str()]);
        if ($start + $dur * 3600 < time()) {
            insert('stamps', ['user_id' => $ids[$i], 'chip_id' => $chip, 'type' => 'gehen', 'stamped_at' => date('Y-m-d H:i:s', $start + $dur * 3600 + 240), 'source' => 'terminal', 'created_at' => now_str()]);
        }
    }
}

q('INSERT INTO audit_log (actor_id, action, details, created_at) VALUES (NULL, ?, ?, ?)', ['chip_erkannt', 'Neuer Chip 04F5A6B7 am Terminal eingang', date('Y-m-d H:i:s', time() - 3600)]);

echo "Demo angelegt.\n";
echo "Admin:       chef@santoloco.test / $pw\n";
echo "Mitarbeiter: max@santoloco.test  / $pw  (auch lea@, jonas@, mia@)\n";
