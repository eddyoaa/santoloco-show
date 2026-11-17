<?php
$maxRow = max(60, ...array_map(fn($r) => max($r['worked'], $r['planned']), $rows ?: [['worked' => 0, 'planned' => 0]]));
$maxWeek = max(60, ...array_map(fn($w) => max($w['worked'], $w['planned']), $weeks ?: [['worked' => 0, 'planned' => 0]]));
$extra = $detail ? ['user' => $detail['person']['id']] : [];
?>
<div class="page-head">
  <div>
    <h1>Auswertungen</h1>
    <div class="sub">Geplante und gearbeitete Stunden, Pünktlichkeit und fehlende Stempel</div>
  </div>
  <div class="actions">
    <?php partial('month_nav', ['page' => 'admin/reports', 'month' => $month, 'extra' => $extra]); ?>
    <a class="btn" href="<?= e(url('admin/reports', ['month' => $month, 'export' => 'csv'])) ?>">CSV exportieren</a>
  </div>
</div>

<div class="grid grid-4">
  <div class="card stat accent"><div class="label">Gearbeitet</div><div class="value"><?= e(fmt_min($totals['worked'])) ?></div><div class="hint">ganzes Team, <?= e(month_label($month)) ?></div></div>
  <div class="card stat"><div class="label">Geplant</div><div class="value"><?= e(fmt_min($totals['planned'])) ?></div><div class="hint">Plus/Minus bis heute: <?= e(fmt_min($totals['diff'], true)) ?></div></div>
  <div class="card stat <?= $totals['late'] ? 'warn' : '' ?>"><div class="label">Verspätungen</div><div class="value"><?= $totals['late'] ?></div><div class="hint"><?= $totals['missing'] ?>× nicht erschienen</div></div>
  <div class="card stat <?= $totals['issues'] ? 'warn' : '' ?>"><div class="label">Fehlende Stempel</div><div class="value"><?= $totals['issues'] ?></div><div class="hint"><a href="<?= e(url('admin/stamps', ['flagged' => 1])) ?>">offene Probleme ansehen</a></div></div>
</div>

<div class="card section">
  <div class="card-head"><h2>Pro Mitarbeiter</h2></div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Mitarbeiter</th><th style="width:28%">Gearbeitet / geplant</th><th class="num">Gearbeitet</th><th class="num">Geplant</th><th class="num" title="Gearbeitet minus geplant bis heute">Plus/Minus</th><th class="num">Tage</th><th class="num">Verspätet</th><th class="num">Gefehlt</th><th class="num">Fehlende Stempel</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr<?= $detail && $detail['person']['id'] === $r['person']['id'] ? ' style="background:#f2f9f9"' : '' ?>>
          <td><span class="person"><span class="avatar sm" style="background:<?= e($r['person']['color']) ?>"><?= e(initials($r['person']['name'])) ?></span><?= e($r['person']['name']) ?></span></td>
          <td><div class="hbar-track"><div class="hbar-plan" style="width:<?= round($r['planned'] / $maxRow * 100, 1) ?>%"></div><div class="hbar-work" style="width:<?= round($r['worked'] / $maxRow * 100, 1) ?>%"></div></div></td>
          <td class="num"><?= e(fmt_min($r['worked'])) ?></td>
          <td class="num"><?= e(fmt_min($r['planned'])) ?></td>
          <td class="num" style="color:<?= $r['diff'] < -60 ? 'var(--orange)' : 'inherit' ?>"><?= e(fmt_min($r['diff'], true)) ?></td>
          <td class="num"><?= $r['days'] ?></td>
          <td class="num"><?= $r['att']['late'] ? '<span class="pill orange">' . $r['att']['late'] . '</span>' : '0' ?></td>
          <td class="num"><?= $r['att']['missing'] ? '<span class="pill red">' . $r['att']['missing'] . '</span>' : '0' ?></td>
          <td class="num"><?= $r['issues'] ? '<span class="pill orange">' . $r['issues'] . '</span>' : '0' ?></td>
          <td class="num"><a class="btn sm" href="<?= e(url('admin/reports', ['month' => $month, 'user' => $r['person']['id']])) ?>#detail">Details</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <div class="legend"><span><i style="background:var(--teal)"></i>gearbeitet</span><span><i style="background:#cfe6e8"></i>geplant</span></div>
</div>

<div class="card section">
  <div class="card-head"><h2>Verlauf nach Wochen</h2><span class="muted small">ganzes Team</span></div>
  <div class="hbars">
    <?php foreach ($weeks as $w): ?>
      <div class="hbar-row">
        <span><?= e($w['label']) ?></span>
        <div class="hbar-track">
          <div class="hbar-plan" style="width:<?= round($w['planned'] / $maxWeek * 100, 1) ?>%"></div>
          <div class="hbar-work" style="width:<?= round($w['worked'] / $maxWeek * 100, 1) ?>%"></div>
        </div>
        <span class="num small"><?= e(fmt_min($w['worked'])) ?> <span class="muted">/ <?= e(fmt_min($w['planned'])) ?></span></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($detail): ?>
<div class="card section" id="detail">
  <div class="card-head">
    <h2>Arbeitszeiten von <?= e($detail['person']['name']) ?> · <?= e(month_label($month)) ?></h2>
    <span class="actions">
      <a class="btn sm" href="<?= e(url('admin/stamps', ['user' => $detail['person']['id'], 'from' => "$month-01", 'to' => date('Y-m-t', ts("$month-01"))])) ?>">Buchungen bearbeiten</a>
      <a class="btn sm" href="<?= e(url('admin/reports', ['month' => $month])) ?>">Schließen</a>
    </span>
  </div>
  <?php partial('sessions_table', ['sessions' => $detail['sessions']]); ?>
</div>
<?php endif; ?>
