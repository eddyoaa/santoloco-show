<div class="page-head">
  <div>
    <h1>Mitarbeiter</h1>
    <div class="sub"><?= count(array_filter($rows, fn($r) => $r['active'])) ?> aktive Konten</div>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
  <table>
    <thead><tr><th>Name</th><th>E-Mail</th><th>Rolle</th><th class="num">Soll / Woche</th><th>NFC-Chip</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="<?= $r['active'] ? '' : 'cancelled' ?>">
          <td><span class="person"><span class="avatar sm" style="background:<?= e($r['color']) ?>"><?= e(initials($r['name'])) ?></span><?= e($r['name']) ?></span></td>
          <td><?= e($r['email']) ?></td>
          <td><?= $r['role'] === 'admin' ? '<span class="pill teal">Admin</span>' : 'Mitarbeiter' ?></td>
          <td class="num"><?= (float) $r['weekly_hours'] > 0 ? e(fmt_min((int) round($r['weekly_hours'] * 60))) : '–' ?></td>
          <td><?= $r['chip_count'] ? '<span class="pill green">' . (int) $r['chip_count'] . ' aktiv</span>' : '<span class="pill orange">keiner</span>' ?></td>
          <td>
            <?php if (!$r['active']): ?><span class="pill">deaktiviert</span>
            <?php elseif ($r['status']['present']): ?><span class="pill green"><span class="dot live"></span>da seit <?= e(fmt_time($r['status']['since'])) ?></span>
            <?php else: ?><span class="muted small">nicht da</span><?php endif; ?>
          </td>
          <td class="num"><a class="btn sm" href="<?= e(url('admin/staff_edit', ['id' => $r['id']])) ?>">Bearbeiten</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>Neuen Mitarbeiter anlegen</h2></div>
  <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" class="form-row">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="create">
    <label class="grow">Name<input name="name" required value="<?= e(input('name')) ?>"></label>
    <label class="grow">E-Mail (zum Anmelden)<input type="email" name="email" required value="<?= e(input('email')) ?>"></label>
    <label>Startpasswort<input type="text" name="password" required minlength="10" value="<?= e(input('password') ?: bin2hex(random_bytes(5))) ?>"></label>
    <label>Soll-Std./Woche<input type="number" name="weekly_hours" min="0" max="60" step="0.5" value="<?= e(input('weekly_hours', '20')) ?>"></label>
    <label>Rolle
      <select name="role"><option value="mitarbeiter">Mitarbeiter</option><option value="admin" <?= input('role') === 'admin' ? 'selected' : '' ?>>Admin</option></select>
    </label>
    <button class="btn primary">Anlegen</button>
  </form>
</div>
