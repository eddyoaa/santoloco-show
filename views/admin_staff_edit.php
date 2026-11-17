<div class="page-head">
  <div>
    <a class="small" href="<?= e(url('admin/staff')) ?>">‹ Alle Mitarbeiter</a>
    <h1 style="margin-top:6px"><span class="person"><span class="avatar" style="background:<?= e($person['color']) ?>"><?= e(initials($person['name'])) ?></span><?= e($person['name']) ?></span></h1>
  </div>
  <div class="actions">
    <a class="btn" href="<?= e(url('admin/reports', ['user' => $person['id']])) ?>">Zeiten & Auswertung</a>
    <a class="btn" href="<?= e(url('admin/stamps', ['user' => $person['id']])) ?>">Buchungen</a>
  </div>
</div>

<?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>

<div class="grid grid-3">
  <div class="card stat <?= $status['present'] ? 'accent' : '' ?>">
    <div class="label">Status</div>
    <div class="value" style="font-size:20px"><?= $status['present'] ? 'Da seit ' . e(fmt_time($status['since'])) : 'Nicht da' ?></div>
  </div>
  <div class="card stat"><div class="label"><?= e(MONTHS[(int) date('n') - 1]) ?> gearbeitet</div><div class="value"><?= e(fmt_min($monthWorked)) ?></div><div class="hint">von <?= e(fmt_min($monthPlanned)) ?> geplant</div></div>
  <div class="card stat"><div class="label">Nächste Schicht</div><div class="value" style="font-size:20px"><?= $upcoming ? e(fmt_day($upcoming[0]['start_at'])) . ' ' . e(fmt_time($upcoming[0]['start_at'])) : '–' ?></div></div>
</div>

<div class="grid grid-2 section">
  <div class="card">
    <div class="card-head"><h2>Stammdaten</h2></div>
    <form method="post" class="stack">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="save">
      <label>Name<input name="name" required value="<?= e($person['name']) ?>"></label>
      <label>E-Mail (Login)<input type="email" name="email" required value="<?= e($person['email']) ?>"></label>
      <div class="form-row">
        <label>Soll-Stunden pro Woche<input type="number" name="weekly_hours" min="0" max="60" step="0.5" value="<?= e((float) $person['weekly_hours']) ?>"></label>
        <label>Rolle
          <select name="role">
            <option value="mitarbeiter">Mitarbeiter</option>
            <option value="admin" <?= $person['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
          </select>
        </label>
        <label style="flex:0 0 auto">Farbe<input type="color" name="color" value="<?= e($person['color']) ?>"></label>
      </div>
      <label>Konto
        <select name="active" <?= $isSelf ? 'disabled' : '' ?>>
          <option value="1">aktiv</option>
          <option value="0" <?= $person['active'] ? '' : 'selected' ?>>deaktiviert (kann sich nicht anmelden, Chips werden gesperrt)</option>
        </select>
        <?php if ($isSelf): ?><input type="hidden" name="active" value="1"><?php endif; ?>
      </label>
      <div><button class="btn primary">Speichern</button></div>
    </form>
  </div>

  <div>
    <div class="card">
      <div class="card-head"><h2>NFC-Chips</h2><a class="small" href="<?= e(url('admin/chips')) ?>">Chips verwalten</a></div>
      <?php if (!$chips): ?>
        <p class="empty">Noch kein Chip zugeordnet. Halte einen neuen Chip ans Terminal und ordne ihn unter „NFC-Chips“ zu.</p>
      <?php else: ?>
        <ul class="list">
          <?php foreach ($chips as $c): ?>
            <li>
              <code><?= e($c['uid']) ?></code>
              <span><?= e($c['label'] ?? '') ?></span>
              <span class="right"><span class="pill <?= $c['status'] === 'aktiv' ? 'green' : 'red' ?>"><?= e($c['status']) ?></span></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-head"><h2>Passwort zurücksetzen</h2></div>
      <form method="post" class="form-row">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="password">
        <label class="grow">Neues Passwort<input type="text" name="password" minlength="10" required value="<?= e(bin2hex(random_bytes(5))) ?>"></label>
        <button class="btn">Setzen</button>
      </form>
      <p class="muted small" style="margin-top:8px">Danach kann <?= e(first_name($person['name'])) ?> es unter „Passwort“ selbst ändern.</p>
    </div>
  </div>
</div>
