<div class="page-head">
  <div>
    <h1>Passwort ändern</h1>
    <div class="sub"><?= e($me['name']) ?> · <?= e($me['email']) ?></div>
  </div>
</div>
<div class="card" style="max-width:480px">
  <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" class="stack">
    <?= csrf_field() ?>
    <label>Aktuelles Passwort<input type="password" name="current" required autocomplete="current-password"></label>
    <label>Neues Passwort (mind. 10 Zeichen)<input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
    <label>Neues Passwort wiederholen<input type="password" name="repeat" required minlength="10" autocomplete="new-password"></label>
    <div><button class="btn primary">Speichern</button></div>
  </form>
</div>
