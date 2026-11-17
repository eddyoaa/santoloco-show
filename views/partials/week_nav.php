<?php /** @var string $page @var string $week @var array $extra */ ?>
<div class="week-nav">
  <a class="btn sm" href="<?= e(url($page, ['week' => add_days($week, -7)] + ($extra ?? []))) ?>" aria-label="Vorige Woche">‹</a>
  <strong>KW <?= (int) date('W', ts($week)) ?> · <?= e(date('d.m.', ts($week))) ?> – <?= e(date('d.m.Y', ts(add_days($week, 6)))) ?></strong>
  <a class="btn sm" href="<?= e(url($page, ['week' => add_days($week, 7)] + ($extra ?? []))) ?>" aria-label="Nächste Woche">›</a>
  <?php if ($week !== monday(date('Y-m-d'))): ?>
    <a class="btn sm" href="<?= e(url($page, $extra ?? [])) ?>">Heute</a>
  <?php endif; ?>
</div>
