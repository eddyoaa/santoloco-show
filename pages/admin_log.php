<?php
$entries = all(
    'SELECT a.*, u.name FROM audit_log a LEFT JOIN users u ON u.id = a.actor_id ORDER BY a.created_at DESC, a.id DESC LIMIT 300'
);
render('admin_log', ['entries' => $entries], 'Protokoll');
