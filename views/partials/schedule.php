<?php
/**
 * Wochenplan: Zeilen = Mitarbeiter, Spalten = Mo–So.
 * @var string $week Montag der Woche (Y-m-d)
 * @var array $people Mitarbeiter
 * @var array $shifts Schichten der Woche
 * @var bool $editable
 * @var ?int $highlight
 */
$byCell = [];
foreach ($shifts as $s) {
    $byCell[$s['user_id']][date('Y-m-d', ts($s['start_at']))][] = $s;
}
$today = date('Y-m-d');
?>
<div class="table-wrap">
<table class="schedule">
  <thead>
    <tr>
      <th style="width:170px">Mitarbeiter</th>
      <?php for ($i = 0; $i < 7; $i++): $d = add_days($week, $i); ?>
        <th class="<?= $d === $today ? 'today' : '' ?>"><?= e(WEEKDAYS[$i]) ?><small><?= e(date('d.m.', ts($d))) ?></small></th>
      <?php endfor; ?>
      <th class="num" style="width:92px">Geplant</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($people as $p): $sum = 0; ?>
      <tr<?= ($highlight ?? null) === (int) $p['id'] ? ' style="background:#f2f9f9"' : '' ?>>
        <td class="who">
          <span class="person"><span class="avatar sm" style="background:<?= e($p['color']) ?>"><?= e(initials($p['name'])) ?></span><?= e($p['name']) ?></span>
        </td>
        <?php for ($i = 0; $i < 7; $i++): $d = add_days($week, $i); ?>
          <td class="<?= $d === $today ? 'today-col' : '' ?>">
            <?php foreach ($byCell[$p['id']][$d] ?? [] as $s): $sum += shift_minutes($s); ?>
              <span class="shift" style="background:<?= e($p['color']) ?>">
                <span class="t"><?= e(fmt_time($s['start_at'])) ?>–<?= e(fmt_time($s['end_at'])) ?></span>
                <?php if ($s['note']): ?><span class="n"><?= e($s['note']) ?></span><?php endif; ?>
                <?php if ($editable): ?>
                  <span class="tools">
                    <a href="<?= e(url('admin/shifts', ['week' => $week, 'edit' => $s['id']])) ?>" title="Bearbeiten">✎</a>
                    <form method="post" class="inline-form" onsubmit="return confirm('Schicht löschen?')">
                      <?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                      <button title="Löschen">✕</button>
                    </form>
                  </span>
                <?php endif; ?>
              </span>
            <?php endforeach; ?>
            <?php if ($editable): ?>
              <a class="small muted" href="<?= e(url('admin/shifts', ['week' => $week, 'user' => $p['id'], 'date' => $d])) ?>#form" title="Schicht hinzufügen">+</a>
            <?php endif; ?>
          </td>
        <?php endfor; ?>
        <td class="sum num">
          <?= e(fmt_min($sum)) ?>
          <?php if ((float) $p['weekly_hours'] > 0): ?><br><small class="muted">Soll <?= e(fmt_min((int) round($p['weekly_hours'] * 60))) ?></small><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
