<div class="page-head">
  <div>
    <h1>Schichtplan</h1>
    <div class="sub"><?= count($shifts) ?> Schichten · <?= e(fmt_min($total)) ?> geplant</div>
  </div>
  <div class="actions">
    <?php partial('week_nav', ['page' => 'admin/shifts', 'week' => $week]); ?>
    <form method="post" class="inline-form" onsubmit="return confirm('Alle Schichten der Vorwoche in diese Woche übernehmen?')">
      <?= csrf_field() ?><input type="hidden" name="do" value="copy"><input type="hidden" name="week" value="<?= e($week) ?>">
      <button class="btn">Vorwoche übernehmen</button>
    </form>
  </div>
</div>

<div class="card" id="form">
  <div class="card-head"><h2><?= $form['id'] ? 'Schicht bearbeiten' : 'Neue Schicht' ?></h2>
    <?php if ($form['id']): ?><a class="small" href="<?= e(url('admin/shifts', ['week' => $week])) ?>">Abbrechen</a><?php endif; ?>
  </div>
  <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
  <?php if (!$people): ?>
    <p class="empty">Lege zuerst <a href="<?= e(url('admin/staff')) ?>">Mitarbeiter</a> an.</p>
  <?php else: ?>
  <form method="post" class="form-row">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="save">
    <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
    <input type="hidden" name="week" value="<?= e($week) ?>">
    <label>Mitarbeiter
      <select name="user_id" required>
        <option value="">Bitte wählen</option>
        <?php foreach ($people as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= $form['user_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Datum<input type="date" name="date" value="<?= e($form['date']) ?>" required></label>
    <label>Von<input type="time" name="from" value="<?= e($form['from']) ?>" required></label>
    <label>Bis<input type="time" name="to" value="<?= e($form['to']) ?>" required></label>
    <label class="grow">Notiz (optional)<input name="note" maxlength="255" value="<?= e($form['note']) ?>" placeholder="z. B. Kasse, Werkstatt, Surfkurs"></label>
    <button class="btn primary"><?= $form['id'] ? 'Speichern' : 'Anlegen' ?></button>
  </form>
  <?php endif; ?>
</div>

<div class="card">
  <?php partial('schedule', ['week' => $week, 'people' => $people, 'shifts' => $shifts, 'editable' => true, 'highlight' => null]); ?>
  <p class="muted small" style="margin-top:10px">Tipp: Mit „+“ in einem Feld legst du dort direkt eine Schicht an. Fahre über eine Schicht, um sie zu bearbeiten oder zu löschen.</p>
</div>
