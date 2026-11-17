<?php
/** @var string $content @var string $title @var ?array $user */
$page = $GLOBALS['current_page'] ?? '';
$issues = ($user && $user['role'] === 'admin') ? open_issues_count() : 0;
$nav = [];
if ($user) {
    if ($user['role'] === 'admin') {
        $nav['Verwaltung'] = [
            ['admin/home', 'Übersicht', 'home'],
            ['admin/shifts', 'Schichtplan', 'calendar'],
            ['admin/stamps', 'Zeiten', 'clock'],
            ['admin/reports', 'Auswertungen', 'chart'],
            ['admin/staff', 'Mitarbeiter', 'users'],
            ['admin/chips', 'NFC-Chips', 'key'],
            ['admin/log', 'Protokoll', 'list'],
        ];
    }
    $nav[$user['role'] === 'admin' ? 'Mein Bereich' : ''] = [
        ['me/dashboard', $user['role'] === 'admin' ? 'Meine Übersicht' : 'Übersicht', 'home'],
        ['me/shifts', 'Schichten', 'calendar'],
        ['me/times', 'Meine Zeiten', 'clock'],
        ['me/profile', 'Passwort', 'lock'],
    ];
}
$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    'calendar' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    'users' => '<circle cx="9" cy="8" r="4"/><path d="M2 21v-1a6 6 0 0 1 12 0v1M16 4a4 4 0 0 1 0 8M22 21v-1a6 6 0 0 0-4-5.7"/>',
    'key' => '<circle cx="7.5" cy="15.5" r="5"/><path d="m11 12 9-9M16 7l3 3"/>',
    'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
];
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ? "$title · " : '') ?><?= e(config('app_name', 'Zeiterfassung')) ?></title>
<link rel="stylesheet" href="assets/app.css">
</head>
<body class="<?= $user ? 'app' : 'auth' ?>">
<?php if ($user): ?>
<aside class="sidebar">
  <a class="brand" href="<?= e(url('dashboard')) ?>">SANTO<span>LOCO</span></a>
  <nav>
    <?php foreach ($nav as $group => $items): ?>
      <?php if ($group): ?><div class="nav-group"><?= e($group) ?></div><?php endif; ?>
      <?php foreach ($items as [$p, $label, $icon]): ?>
        <a href="<?= e(url($p)) ?>" class="<?= $page === $p ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons[$icon] ?></svg>
          <span><?= e($label) ?></span>
          <?php if ($p === 'admin/home' && $issues > 0): ?><b class="badge"><?= $issues ?></b><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-user">
    <span class="avatar" style="background:<?= e($user['color']) ?>"><?= e(initials($user['name'])) ?></span>
    <div><strong><?= e($user['name']) ?></strong><small><?= $user['role'] === 'admin' ? 'Admin' : 'Mitarbeiter' ?></small></div>
    <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button class="link" title="Abmelden">Abmelden</button></form>
  </div>
</aside>
<main class="main">
  <?php foreach (take_flashes() as [$type, $msg]): ?>
    <div class="alert <?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>
<?php else: ?>
<?= $content ?>
<?php endif; ?>
</body>
</html>
