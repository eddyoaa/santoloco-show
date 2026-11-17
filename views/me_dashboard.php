<?php
$next = $upcoming[0] ?? null;
$maxBar = max(60, ...array_map(fn($d) => max($d['worked'], $d['planned']), $days));
$weekDiff = $weekWorked - $weekPlannedToDate;
?>
<div class="page-head">
  <div>
    <h1>Hallo <?= e(first_name($me['name'])) ?></h1>
    <div class="sub"><?= e(fmt_day(date('Y-m-d'))) ?> · Kalenderwoche <?= (int) date('W') ?></div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="hero">
      <?php if ($status['present']): ?>
        <div class="hero-icon on"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></div>
        <div>
          <div class="big">Eingestempelt seit <?= e(fmt_time($status['since'])) ?></div>
          <div class="muted">Bisher heute: <?= e(fmt_min(intdiv(time() - ts($status['since']), 60))) ?></div>
        </div>
      <?php else: ?>
        <div class="hero-icon off"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        <div>
          <div class="big">Nicht eingestempelt</div>
          <div class="muted">
            <?php if ($status['last']): ?>
              Zuletzt <?= $status['last']['type'] === 'kommen' ? 'eingestempelt' : 'ausgestempelt' ?>: <?= e(fmt_day($status['last']['stamped_at'])) ?>, <?= e(fmt_time($status['last']['stamped_at'])) ?>
            <?php else: ?>
              Noch keine Buchungen.
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <?php if ($chips === 0): ?>
      <p class="alert warn" style="margin:14px 0 0">Dir ist noch kein NFC-Chip zugeordnet. Bitte beim Chef melden.</p>
    <?php endif; ?>
  </div>

  <?php if ($next): ?>
    <div class="card next-shift">
      <div class="label"><?= ts($next['start_at']) <= time() ? 'Aktuelle Schicht' : 'Nächste Schicht' ?></div>
      <div class="when"><?= e(date('Y-m-d', ts($next['start_at'])) === date('Y-m-d') ? 'Heute' : (date('Y-m-d', ts($next['start_at'])) === add_days(date('Y-m-d'), 1) ? 'Morgen' : fmt_day($next['start_at']))) ?></div>
      <div class="time"><?= e(fmt_time($next['start_at'])) ?> – <?= e(fmt_time($next['end_at'])) ?> Uhr<?= $next['note'] ? ' · ' . e($next['note']) : '' ?></div>
    </div>
  <?php else: ?>
    <div class="card"><div class="label muted">Nächste Schicht</div><p class="empty">Keine Schichten geplant.</p></div>
  <?php endif; ?>
</div>

<div class="grid grid-4 section">
  <div class="card stat">
    <div class="label">Diese Woche</div>
    <div class="value"><?= e(fmt_min($weekWorked)) ?></div>
    <div class="hint">von <?= e(fmt_min($weekPlanned)) ?> geplant</div>
  </div>
  <div class="card stat <?= $weekDiff < 0 ? '' : 'accent' ?>">
    <div class="label">Plus/Minus Woche</div>
    <div class="value"><?= e(fmt_min($weekDiff, true)) ?></div>
    <div class="hint">gegenüber Plan bis heute</div>
  </div>
  <div class="card stat">
    <div class="label"><?= e(MONTHS[(int) date('n') - 1]) ?></div>
    <div class="value"><?= e(fmt_min($monthWorked)) ?></div>
    <div class="hint">von <?= e(fmt_min($monthPlanned)) ?> geplant</div>
  </div>
  <div class="card stat <?= $monthAttendance['late'] > 0 ? 'warn' : '' ?>">
    <div class="label">Pünktlichkeit <?= e(MONTHS[(int) date('n') - 1]) ?></div>
    <div class="value"><?= $monthAttendance['shifts'] - $monthAttendance['late'] - $monthAttendance['missing'] ?>/<?= $monthAttendance['shifts'] ?></div>
    <div class="hint">Schichten pünktlich<?= $monthAttendance['late'] ? ' · ' . $monthAttendance['late'] . '× verspätet' : '' ?></div>
  </div>
</div>

<div class="grid grid-2 section">
  <div class="card">
    <div class="card-head"><h2>Meine Woche</h2><a href="<?= e(url('me/times')) ?>" class="small">Alle Zeiten</a></div>
    <div class="vbars">
      <?php foreach ($days as $d): ?>
        <div class="vbar <?= $d['date'] === date('Y-m-d') ? 'today' : '' ?>">
          <span class="val"><?= $d['worked'] ? e(rtrim(rtrim(number_format($d['worked'] / 60, 1, ',', ''), '0'), ',')) . ' h' : '' ?></span>
          <div class="vbar-col">
            <?php if ($d['planned']): ?><div class="vbar-plan" style="height:<?= round($d['planned'] / $maxBar * 100) ?>%"></div><?php endif; ?>
            <div class="vbar-fill" style="height:<?= round($d['worked'] / $maxBar * 100) ?>%"></div>
          </div>
          <small><?= e(wd($d['date'])) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="legend"><span><i style="background:var(--teal)"></i>gearbeitet</span><span><i style="border:2px dashed #9cc9cd"></i>geplant</span></div>
  </div>

  <div class="card">
    <div class="card-head"><h2>Nächste Schichten</h2><a href="<?= e(url('me/shifts')) ?>" class="small">Schichtplan</a></div>
    <?php if (!$upcoming): ?>
      <p class="empty">Keine Schichten geplant.</p>
    <?php else: ?>
      <ul class="list shift-list">
        <?php foreach ($upcoming as $s): ?>
          <li>
            <span class="day"><?= e(fmt_day($s['start_at'])) ?></span>
            <span><?= e(fmt_time($s['start_at'])) ?> – <?= e(fmt_time($s['end_at'])) ?></span>
            <span class="right muted small"><?= e($s['note'] ?? '') ?> <?= e(fmt_min(shift_minutes($s))) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="card section">
  <div class="card-head"><h2>Letzte Buchungen</h2></div>
  <?php if (!$recent): ?>
    <p class="empty">Noch keine Buchungen.</p>
  <?php else: ?>
    <ul class="list">
      <?php foreach ($recent as $r): ?>
        <li>
          <span class="pill <?= $r['type'] === 'kommen' ? 'green' : 'teal' ?>"><?= $r['type'] === 'kommen' ? 'Kommen' : 'Gehen' ?></span>
          <span><?= e(fmt_day($r['stamped_at'])) ?>, <?= e(fmt_time($r['stamped_at'])) ?> Uhr</span>
          <span class="right">
            <?php if ($r['flag']): ?><span class="pill orange"><?= e(FLAG_LABELS[$r['flag']] ?? $r['flag']) ?></span><?php endif; ?>
            <?php if ($r['source'] === 'admin'): ?><span class="pill">nachgetragen</span><?php endif; ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
