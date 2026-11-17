<main class="auth-card">
  <div class="brand">SANTO<span>LOCO</span></div>
  <h1>Anmelden</h1>
  <p class="muted">Zeiterfassung und Schichtplan</p>
  <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" class="stack" action="<?= e(url('login')) ?>">
    <?= csrf_field() ?>
    <label>E-Mail<input type="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username"></label>
    <label>Passwort<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn primary">Anmelden</button>
  </form>
</main>
