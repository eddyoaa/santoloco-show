<?php
declare(strict_types=1);

/** Creates all tables if they do not exist yet. Works with MySQL/MariaDB and SQLite. */
function install_schema(): void
{
    $mysql = db_driver() === 'mysql';
    $id = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $suffix = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $tables = [
        'users' => [
            "id $id",
            'name VARCHAR(100) NOT NULL',
            'email VARCHAR(190) NOT NULL UNIQUE',
            'password_hash VARCHAR(255) NOT NULL',
            "role VARCHAR(20) NOT NULL DEFAULT 'mitarbeiter'",
            'active TINYINT NOT NULL DEFAULT 1',
            'weekly_hours DECIMAL(5,2) NOT NULL DEFAULT 0',
            "color VARCHAR(7) NOT NULL DEFAULT '#0e7c86'",
            'failed_logins INT NOT NULL DEFAULT 0',
            'locked_until DATETIME NULL',
            'created_at DATETIME NOT NULL',
        ],
        'chips' => [
            "id $id",
            'uid VARCHAR(64) NOT NULL UNIQUE',
            'user_id INT NULL',
            'label VARCHAR(100) NULL',
            "status VARCHAR(20) NOT NULL DEFAULT 'aktiv'",
            'last_seen_at DATETIME NULL',
            'created_at DATETIME NOT NULL',
        ],
        'shifts' => [
            "id $id",
            'user_id INT NOT NULL',
            'start_at DATETIME NOT NULL',
            'end_at DATETIME NOT NULL',
            'note VARCHAR(255) NULL',
            'created_at DATETIME NOT NULL',
        ],
        'stamps' => [
            "id $id",
            'user_id INT NOT NULL',
            'chip_id INT NULL',
            'type VARCHAR(10) NOT NULL',
            'stamped_at DATETIME NOT NULL',
            "source VARCHAR(20) NOT NULL DEFAULT 'terminal'",
            'flag VARCHAR(40) NULL',
            'note VARCHAR(255) NULL',
            'cancelled_at DATETIME NULL',
            'created_at DATETIME NOT NULL',
        ],
        'audit_log' => [
            "id $id",
            'actor_id INT NULL',
            'action VARCHAR(60) NOT NULL',
            'details TEXT NULL',
            'created_at DATETIME NOT NULL',
        ],
    ];
    $indexes = [
        'chips' => ['idx_chips_user' => 'user_id'],
        'shifts' => ['idx_shifts_user_start' => 'user_id, start_at', 'idx_shifts_start' => 'start_at'],
        'stamps' => ['idx_stamps_user_time' => 'user_id, stamped_at', 'idx_stamps_flag' => 'flag'],
        'audit_log' => ['idx_audit_created' => 'created_at'],
    ];

    foreach ($tables as $name => $cols) {
        if ($mysql) {
            foreach ($indexes[$name] ?? [] as $idx => $on) {
                $cols[] = "INDEX $idx ($on)";
            }
        }
        db()->exec("CREATE TABLE IF NOT EXISTS $name (\n  " . implode(",\n  ", $cols) . "\n)$suffix");
        if (!$mysql) {
            foreach ($indexes[$name] ?? [] as $idx => $on) {
                db()->exec("CREATE INDEX IF NOT EXISTS $idx ON $name ($on)");
            }
        }
    }
}

function create_user(string $name, string $email, string $password, string $role = 'mitarbeiter', float $weeklyHours = 0, string $color = '#0e7c86'): int
{
    return insert('users', [
        'name' => $name,
        'email' => mb_strtolower(trim($email)),
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'active' => 1,
        'weekly_hours' => $weeklyHours,
        'color' => $color,
        'created_at' => now_str(),
    ]);
}
