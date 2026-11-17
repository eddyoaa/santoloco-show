<?php $new = array_filter($chips, fn($c) => !$c['user_id'] && $c['status'] === 'aktiv'); ?>
<div class="page-head">
  <div>
    <h1>NFC-Chips</h1>
    <div class="sub"><?= count($chips) ?> Chips · <?= count($new) ?> nicht zugeordnet</div>
  </div>
</div>

<div class="alert">
  <strong>So ordnest du einen neuen Chip zu:</strong> Halte den Chip einmal ans Terminal. Er erscheint dann hier oben als „Neu“. Wähle den Mitarbeiter aus und klicke auf „Speichern“.
</div>

<?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>

<div class="card">
  <?php if (!$chips): ?>
    <p class="empty">Noch keine Chips. Halte einen Chip ans Terminal oder trage die Nummer unten ein.</p>
  <?php else: ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Chip-Nummer</th><th>Mitarbeiter</th><th>Bezeichnung</th><th>Status</th><th>Zuletzt benutzt</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($chips as $c): $isNew = !$c['user_id'] && $c['status'] === 'aktiv'; $fid = 'chip' . (int) $c['id']; ?>
        <tr class="<?= $isNew ? 'flagged' : '' ?>">
          <td><code><?= e($c['uid']) ?></code> <?php if ($isNew): ?><span class="pill orange">Neu</span><?php endif; ?></td>
          <td>
            <select name="user_id" form="<?= $fid ?>">
              <option value="">– niemand –</option>
              <?php foreach ($people as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= (int) $c['user_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input name="label" form="<?= $fid ?>" value="<?= e($c['label'] ?? '') ?>" placeholder="z. B. blauer Anhänger" maxlength="100"></td>
          <td><span class="pill <?= $c['status'] === 'aktiv' ? 'green' : 'red' ?>"><?= e($c['status']) ?></span></td>
          <td class="small muted nowrap"><?= e(fmt_dt($c['last_seen_at'])) ?></td>
          <td class="num">
            <form method="post" id="<?= $fid ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn sm primary">Speichern</button></form>
            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <?php if ($c['status'] === 'aktiv'): ?>
                <input type="hidden" name="do" value="block"><button class="btn sm">Sperren</button>
              <?php else: ?>
                <input type="hidden" name="do" value="unblock"><button class="btn sm">Freigeben</button>
              <?php endif; ?>
            </form>
            <form method="post" class="inline-form" onsubmit="return confirm('Chip <?= e($c['uid']) ?> löschen? Die Buchungen bleiben erhalten.')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn sm danger">Löschen</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-head"><h2>Chip manuell eintragen</h2></div>
  <form method="post" class="form-row">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="add">
    <label>Chip-Nummer (UID)<input name="uid" required placeholder="04A31F7C" value="<?= e(input('uid')) ?>"></label>
    <label>Mitarbeiter
      <select name="user_id"><option value="">– später zuordnen –</option>
        <?php foreach ($people as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="grow">Bezeichnung<input name="label" maxlength="100" placeholder="optional"></label>
    <button class="btn">Eintragen</button>
  </form>
</div>
