<?php
/** @var array $sessions Arbeitssitzungen, optional mit 'person' */
$showPerson = $showPerson ?? false;
$total = array_sum(array_map(fn($s) => (int) $s['minutes'], $sessions));
?>
<?php if (!$sessions): ?>
  <p class="empty">Keine Zeiten in diesem Zeitraum.</p>
<?php else: ?>
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <?php if ($showPerson): ?><th>Mitarbeiter</th><?php endif; ?>
      <th>Tag</th><th>Kommen</th><th>Gehen</th><th class="num">Dauer</th><th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($sessions as $s): ?>
      <tr class="<?= in_array($s['status'], ['offen', 'ohne_kommen'], true) ? 'flagged' : '' ?>">
        <?php if ($showPerson): ?><td><?= e($s['person']) ?></td><?php endif; ?>
        <td class="nowrap"><?= e(fmt_day($s['ref'])) ?></td>
        <td class="num" style="text-align:left"><?= e(fmt_time($s['start'])) ?></td>
        <td class="num" style="text-align:left"><?= e(fmt_time($s['end'])) ?></td>
        <td class="num"><?= e(fmt_min($s['minutes'])) ?></td>
        <td>
          <?php
          $cls = ['ok' => 'green', 'laufend' => 'teal', 'offen' => 'orange', 'ohne_kommen' => 'orange'][$s['status']];
          $edited = ($s['in']['source'] ?? '') === 'admin' || ($s['out']['source'] ?? '') === 'admin';
          ?>
          <span class="pill <?= $cls ?>"><?php if ($s['status'] === 'laufend'): ?><span class="dot live"></span><?php endif; ?><?= e(SESSION_LABELS[$s['status']]) ?></span>
          <?php if ($edited): ?><span class="pill">nachgetragen</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="<?= $showPerson ? 4 : 3 ?>">Summe</td>
      <td class="num"><?= e(fmt_min($total)) ?></td>
      <td></td>
    </tr>
  </tfoot>
</table>
</div>
<?php endif; ?>
