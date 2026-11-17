<?php
$attLabels = [
    'puenktlich' => ['pünktlich', 'green'],
    'verspaetet' => ['verspätet', 'orange'],
    'fehlt' => ['nicht erschienen', 'red'],
    'ueberfaellig' => ['noch nicht da', 'red'],
    'geplant' => ['geplant', ''],
];
$maxBar = max(60, ...array_map(fn($r) => max($r['planned'], $r['worked']), $weekRows ?: [['planned' => 0, 'worked' => 0]]));
$openIssues = $issues['chips'] + $issues['forgotten'] + $issues['other'];
?>
<div class="page-head">
  <div>
    <h1>Übersicht</h1>
    <div class="sub"><?= e(fmt_day(date('Y-m-d'))) ?> · KW <?= (int) date('W') ?></div>
  </div>
  <div class="actions">
    <a class="btn" href="<?= e(url('admin/shifts')) ?>">Schichtplan</a>
    <a class="btn primary" href="<?= e(url('admin/reports')) ?>">Auswertungen</a>
  </div>
</div>

<div class="grid grid-4">
  <div class="card stat accent">
    <div class="label">Jetzt im Laden</div>
    <div class="value"><?= count($present) ?></div>
    <div class="hint">von <?= $staffCount ?> Mitarbeitern</div>
  </div>
  <div class="card stat">
    <div class="label">Schichten heute</div>
    <div class="value"><?= count($todayShifts) ?></div>
    <div class="hint"><?= count(array_filter($todayShifts, fn($s) => in_array($s['att']['state'], ['verspaetet', 'fehlt', 'ueberfaellig'], true))) ?> mit Auffälligkeit</div>
  </div>
  <div class="card stat">
    <div class="label">Team diese Woche</div>
    <div class="value"><?= e(fmt_min($weekWorked)) ?></div>
    <div class="hint">von <?= e(fmt_min($weekPlanned)) ?> geplant</div>
  </div>
  <div class="card stat <?= $openIssues ? 'warn' : '' ?>">
    <div class="label">Offene Punkte</div>
    <div class="value"><?= $openIssues ?></div>
    <div class="hint"><?= $openIssues ? 'bitte prüfen' : 'alles erledigt' ?></div>
  </div>
</div>

<?php if ($openIssues): ?>
<div class="card section">
  <div class="card-head"><h2>Zu erledigen</h2></div>
  <ul class="list">
    <?php if ($issues['chips']): ?>
      <li><span class="pill orange">Chips</span><span><?= $issues['chips'] ?> neue<?= $issues['chips'] === 1 ? 'r' : '' ?> Chip<?= $issues['chips'] === 1 ? '' : 's' ?> noch keinem Mitarbeiter zugeordnet</span><a class="right btn sm" href="<?= e(url('admin/chips')) ?>">Zuordnen</a></li>
    <?php endif; ?>
    <?php if ($issues['forgotten']): ?>
      <li><span class="pill orange">Zeiten</span><span><?= $issues['forgotten'] ?>× Ausstempeln vergessen</span><a class="right btn sm" href="<?= e(url('admin/stamps', ['flagged' => 1])) ?>">Nachtragen</a></li>
    <?php endif; ?>
    <?php if ($issues['other']): ?>
      <li><span class="pill orange">Zeiten</span><span><?= $issues['other'] ?> Buchung<?= $issues['other'] === 1 ? '' : 'en' ?> zum Prüfen</span><a class="right btn sm" href="<?= e(url('admin/stamps', ['flagged' => 1])) ?>">Prüfen</a></li>
    <?php endif; ?>
  </ul>
</div>
<?php endif; ?>

<div class="grid grid-2 section">
  <div class="card">
    <div class="card-head"><h2>Jetzt im Laden</h2></div>
    <?php if (!$present): ?>
      <p class="empty">Gerade ist niemand eingestempelt.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($present as $r): ?>
          <li>
            <span class="avatar sm" style="background:<?= e($r['person']['color']) ?>"><?= e(initials($r['person']['name'])) ?></span>
            <span><?= e($r['person']['name']) ?></span>
            <span class="right small"><span class="pill green"><span class="dot live"></span>seit <?= e(fmt_time($r['since'])) ?></span></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head"><h2>Schichten heute</h2><a class="small" href="<?= e(url('admin/shifts')) ?>">Plan öffnen</a></div>
    <?php if (!$todayShifts): ?>
      <p class="empty">Heute sind keine Schichten geplant.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($todayShifts as $s): [$lbl, $cls] = $attLabels[$s['att']['state']]; ?>
          <li>
            <span class="avatar sm" style="background:<?= e($s['color']) ?>"><?= e(initials($s['name'])) ?></span>
            <span><?= e($s['name']) ?><br><small class="muted"><?= e(fmt_time($s['start_at'])) ?> – <?= e(fmt_time($s['end_at'])) ?></small></span>
            <span class="right"><span class="pill <?= $cls ?>"><?= e($lbl) ?><?= $s['att']['at'] ? ' ' . e(fmt_time($s['att']['at'])) : '' ?></span></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="card section">
  <div class="card-head"><h2>Stunden diese Woche</h2><span class="muted small">KW <?= (int) date('W') ?></span></div>
  <div class="hbars">
    <?php foreach ($weekRows as $r): ?>
      <div class="hbar-row">
        <span class="person"><span class="avatar sm" style="background:<?= e($r['person']['color']) ?>"><?= e(initials($r['person']['name'])) ?></span><?= e(first_name($r['person']['name'])) ?></span>
        <div class="hbar-track">
          <div class="hbar-plan" style="width:<?= round($r['planned'] / $maxBar * 100, 1) ?>%"></div>
          <div class="hbar-work" style="width:<?= round($r['worked'] / $maxBar * 100, 1) ?>%"></div>
        </div>
        <span class="num small"><?= e(fmt_min($r['worked'])) ?> <span class="muted">/ <?= e(fmt_min($r['planned'])) ?></span></span>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="legend"><span><i style="background:var(--teal)"></i>gearbeitet</span><span><i style="background:#cfe6e8"></i>geplant</span></div>
</div>
