<div class="page-head">
  <div>
    <h1>Meine Zeiten</h1>
    <div class="sub">Alle Buchungen und Arbeitsstunden pro Monat</div>
  </div>
  <div class="actions">
    <?php partial('month_nav', ['page' => 'me/times', 'month' => $month]); ?>
    <a class="btn" href="<?= e(url('me/times', ['month' => $month, 'export' => 'csv'])) ?>">CSV exportieren</a>
  </div>
</div>

<div class="grid grid-4">
  <div class="card stat accent"><div class="label">Gearbeitet</div><div class="value"><?= e(fmt_min($worked)) ?></div><div class="hint"><?= e(month_label($month)) ?></div></div>
  <div class="card stat"><div class="label">Geplant</div><div class="value"><?= e(fmt_min($planned)) ?></div><div class="hint">laut Schichtplan</div></div>
  <div class="card stat"><div class="label">Plus/Minus</div><div class="value"><?= e(fmt_min($worked - $plannedToDate, true)) ?></div><div class="hint">gegenüber Plan bis heute</div></div>
  <div class="card stat <?= $att['late'] ? 'warn' : '' ?>"><div class="label">Verspätungen</div><div class="value"><?= $att['late'] ?></div><div class="hint">bei <?= $att['shifts'] ?> Schichten</div></div>
</div>

<div class="card section">
  <div class="card-head"><h2>Arbeitszeiten</h2></div>
  <?php partial('sessions_table', ['sessions' => $sessions]); ?>
  <p class="muted small" style="margin-top:12px">Fehlt eine Buchung oder stimmt etwas nicht? Sag dem Chef Bescheid, er kann Zeiten nachtragen.</p>
</div>
