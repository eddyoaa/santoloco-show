<?php
/** @var string $page @var string $month @var array $extra */
$prev = date('Y-m', strtotime("$month-01 -1 month"));
$nextM = date('Y-m', strtotime("$month-01 +1 month"));
?>
<div class="week-nav">
  <a class="btn sm" href="<?= e(url($page, ['month' => $prev] + ($extra ?? []))) ?>" aria-label="Voriger Monat">‹</a>
  <strong><?= e(month_label($month)) ?></strong>
  <a class="btn sm" href="<?= e(url($page, ['month' => $nextM] + ($extra ?? []))) ?>" aria-label="Nächster Monat">›</a>
</div>
