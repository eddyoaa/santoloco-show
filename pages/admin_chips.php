<?php
$error = '';

if (is_post()) {
    $do = input('do');

    if ($do === 'add') {
        $uid = normalize_uid(input('uid'));
        if ($uid === '' || strlen($uid) > 32) {
            $error = 'Bitte eine gültige Chip-Nummer eingeben (Hex, z. B. 04A31F7C).';
        } elseif (val('SELECT COUNT(*) FROM chips WHERE uid = ?', [$uid])) {
            $error = "Der Chip $uid ist schon angelegt.";
        } else {
            $userId = (int) input('user_id') ?: null;
            insert('chips', ['uid' => $uid, 'user_id' => $userId, 'label' => input('label') ?: null, 'status' => 'aktiv', 'created_at' => now_str()]);
            audit('chip_angelegt', $uid);
            flash("Chip $uid angelegt.");
            redirect('admin/chips');
        }
    }

    $chip = in_array($do, ['save', 'block', 'unblock', 'delete'], true) ? one('SELECT * FROM chips WHERE id = ?', [(int) input('id')]) : null;
    if ($chip) {
        if ($do === 'save') {
            $userId = (int) input('user_id') ?: null;
            $owner = $userId ? one('SELECT name FROM users WHERE id = ?', [$userId]) : null;
            q('UPDATE chips SET user_id = ?, label = ? WHERE id = ?', [$owner ? $userId : null, mb_substr(input('label'), 0, 100) ?: null, $chip['id']]);
            audit('chip_zugeordnet', "{$chip['uid']} → " . ($owner['name'] ?? 'niemand'));
            flash($owner ? "Chip {$chip['uid']} ist jetzt {$owner['name']} zugeordnet." : "Chip {$chip['uid']} gespeichert.");
        } elseif ($do === 'block' || $do === 'unblock') {
            $status = $do === 'block' ? 'gesperrt' : 'aktiv';
            q('UPDATE chips SET status = ? WHERE id = ?', [$status, $chip['id']]);
            audit("chip_$status", $chip['uid']);
            flash("Chip {$chip['uid']} " . ($do === 'block' ? 'gesperrt.' : 'wieder freigegeben.'));
        } elseif ($do === 'delete') {
            q('UPDATE stamps SET chip_id = NULL WHERE chip_id = ?', [$chip['id']]);
            q('DELETE FROM chips WHERE id = ?', [$chip['id']]);
            audit('chip_geloescht', $chip['uid']);
            flash("Chip {$chip['uid']} gelöscht.");
        }
        redirect('admin/chips');
    }
}

$chips = all(
    "SELECT c.*, u.name, u.color, u.active AS user_active FROM chips c LEFT JOIN users u ON u.id = c.user_id
     ORDER BY CASE WHEN c.user_id IS NULL AND c.status = 'aktiv' THEN 0 ELSE 1 END, c.status, u.name, c.created_at DESC"
);

render('admin_chips', ['chips' => $chips, 'people' => active_users(), 'error' => $error], 'NFC-Chips');
