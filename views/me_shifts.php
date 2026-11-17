<div class="page-head">
  <div>
    <h1>Schichten</h1>
    <div class="sub">Deine nächsten 4 Wochen und der Plan fürs ganze Team</div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>Meine nächsten Schichten</h2><span class="muted small"><?= count($upcoming) ?> Schichten · <?= e(fmt_min(array_sum(array_map('shift_minutes', $upcoming)))) ?></span></div>
  <?php if (!$upcoming): ?>
    <p class="empty">In den nächsten 4 Wochen sind keine Schichten für dich geplant.</p>
  <?php else: ?>
    <ul class="list shift-list">
      <?php $lastWeek = null; foreach ($upcoming as $s): $w = (int) date('W', ts($s['start_at'])); ?>
        <?php if ($w !== $lastWeek): $lastWeek = $w; ?>
          <li class="muted small" style="border:none;padding-bottom:0;padding-top:14px"><strong>KW <?= $w ?></strong></li>
        <?php endif; ?>
        <li>
          <span class="day"><?= e(fmt_day($s['start_at'])) ?></span>
          <span><?= e(fmt_time($s['start_at'])) ?> – <?= e(fmt_time($s['end_at'])) ?> Uhr</span>
          <span class="right muted small"><?= e($s['note'] ?? '') ?> <?= e(fmt_min(shift_minutes($s))) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-head">
    <h2>Team-Plan</h2>
    <?php partial('week_nav', ['page' => 'me/shifts', 'week' => $week]); ?>
  </div>
  <?php partial('schedule', ['week' => $week, 'people' => $people, 'shifts' => $shifts, 'editable' => false, 'highlight' => (int) $me['id']]); ?>
</div>
