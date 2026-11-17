<div class="page-head">
  <div>
    <h1>Protokoll</h1>
    <div class="sub">Wer hat wann was geändert. Die letzten 300 Einträge.</div>
  </div>
</div>
<div class="card">
  <?php if (!$entries): ?>
    <p class="empty">Noch keine Einträge.</p>
  <?php else: ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Zeit</th><th>Wer</th><th>Aktion</th><th>Details</th></tr></thead>
    <tbody>
      <?php foreach ($entries as $a): ?>
        <tr>
          <td class="nowrap small"><?= e(fmt_dt($a['created_at'])) ?></td>
          <td class="nowrap"><?= e($a['name'] ?? 'Terminal') ?></td>
          <td><span class="pill"><?= e(str_replace('_', ' ', $a['action'])) ?></span></td>
          <td class="small"><?= e($a['details'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
