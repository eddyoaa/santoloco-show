<div class="page-head">
  <div>
    <h1>Zeiten</h1>
    <div class="sub">Alle Stempelbuchungen. Hier kannst du nachtragen, korrigieren und stornieren.</div>
  </div>
  <div class="actions">
    <a class="btn" href="<?= e(url('admin/stamps', $filter + ['export' => 'csv'])) ?>">Arbeitszeiten als CSV</a>
  </div>
</div>

<div class="card">
  <form method="get" class="form-row">
    <input type="hidden" name="p" value="admin/stamps">
    <label>Mitarbeiter
      <select name="user"><option value="">Alle</option>
        <?php foreach ($people as $p): ?><option value="<?= (int) $p['id'] ?>" <?= $userId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Von<input type="date" name="from" value="<?= e($from) ?>"></label>
    <label>Bis<input type="date" name="to" value="<?= e($to) ?>"></label>
    <label class="check" style="flex:0 0 auto;padding-bottom:9px"><input type="checkbox" name="flagged" value="1" <?= $flagged ? 'checked' : '' ?>> nur Probleme</label>
    <button class="btn">Anzeigen</button>
  </form>
</div>

<div class="card">
  <div class="card-head"><h2>Buchungen</h2><span class="muted small"><?= count($stamps) ?> Einträge</span></div>
  <?php if (!$stamps): ?>
    <p class="empty"><?= $flagged ? 'Keine offenen Probleme. 👍' : 'Keine Buchungen in diesem Zeitraum.' ?></p>
  <?php else: ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Mitarbeiter</th><th>Art</th><th>Zeit</th><th>Quelle</th><th>Hinweis</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($stamps as $s): $fid = 'st' . (int) $s['id']; ?>
        <tr class="<?= $s['cancelled_at'] ? 'cancelled' : ($s['flag'] ? 'flagged' : '') ?>">
          <td><span class="person"><span class="avatar sm" style="background:<?= e($s['color']) ?>"><?= e(initials($s['name'])) ?></span><?= e($s['name']) ?></span></td>
          <td><span class="pill <?= $s['type'] === 'kommen' ? 'green' : 'teal' ?>"><?= $s['type'] === 'kommen' ? 'Kommen' : 'Gehen' ?></span></td>
          <td class="nowrap">
            <?php if ($s['cancelled_at']): ?>
              <?= e(fmt_day($s['stamped_at'])) ?> <?= e(fmt_time($s['stamped_at'])) ?>
            <?php else: ?>
              <form method="post" id="<?= $fid ?>" class="inline-form">
                <?= csrf_field() ?><input type="hidden" name="do" value="edit"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <input type="datetime-local" name="stamped_at" value="<?= e(date('Y-m-d\TH:i', ts($s['stamped_at']))) ?>" style="min-height:32px;padding:4px 8px">
              </form>
              <small class="muted"><?= e(wd($s['stamped_at'])) ?></small>
            <?php endif; ?>
          </td>
          <td class="small"><?= $s['source'] === 'admin' ? 'nachgetragen' : 'Terminal' ?><?= $s['chip_uid'] ? '<br><code>' . e($s['chip_uid']) . '</code>' : '' ?></td>
          <td class="small">
            <?php if ($s['cancelled_at']): ?>storniert
            <?php elseif ($s['flag']): ?><span class="pill orange"><?= e(FLAG_LABELS[$s['flag']] ?? $s['flag']) ?></span>
            <?php endif; ?>
            <?= $s['note'] ? '<br>' . e($s['note']) : '' ?>
          </td>
          <td class="num">
            <?php if ($s['cancelled_at']): ?>
              <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="do" value="restore"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button class="btn sm">Wiederherstellen</button></form>
            <?php else: ?>
              <button class="btn sm primary" form="<?= $fid ?>">Speichern</button>
              <?php if ($s['flag']): ?>
                <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="do" value="resolve"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button class="btn sm">Erledigt</button></form>
              <?php endif; ?>
              <form method="post" class="inline-form" onsubmit="return confirm('Buchung stornieren?')"><?= csrf_field() ?><input type="hidden" name="do" value="cancel"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button class="btn sm danger">Stornieren</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-head"><h2>Buchung nachtragen</h2></div>
  <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" class="form-row">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="add">
    <label>Mitarbeiter
      <select name="user_id" required><option value="">Bitte wählen</option>
        <?php foreach ($people as $p): if (!$p['active']) continue; ?><option value="<?= (int) $p['id'] ?>" <?= $userId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Art<select name="type"><option value="kommen">Kommen</option><option value="gehen" selected>Gehen</option></select></label>
    <label>Datum<input type="date" name="date" value="<?= e(date('Y-m-d')) ?>" required></label>
    <label>Uhrzeit<input type="time" name="time" required></label>
    <label class="grow">Grund (optional)<input name="note" maxlength="255" placeholder="z. B. Ausstempeln vergessen"></label>
    <button class="btn primary">Nachtragen</button>
  </form>
  <p class="muted small" style="margin-top:8px">Alle Änderungen werden im Protokoll festgehalten. Der Mitarbeiter sieht nachgetragene Zeiten als „nachgetragen“.</p>
</div>
