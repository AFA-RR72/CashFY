<?php session_start();
require_once("../model/institutes.php");
$institutes = get_institutes();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Criar conta — Cashfy</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="auth-page">
    <a href="login.php" class="auth-back">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
        stroke-linecap="round">
        <line x1="19" y1="12" x2="5" y2="12" />
        <polyline points="12 19 5 12 12 5" />
      </svg>
      Voltar
    </a>

    <div class="auth-card">
      <p class="brand"><span class="brand-mark"></span> Cashfy</p>
      <p class="auth-sub">Seja bem-vindo! Crie sua conta.</p>

      <form id="signup-form" method="post" action="../controller/cadastro.php" novalidate>
        <div class="field" id="f-name">
          <label for="name">Nome completo</label>
          <input type="text" id="name" name="name" placeholder="Seu nome completo" autocomplete="name" required>
        </div>
        <div class="field">
          <label for="institute">Instituição de ensino</label>

          <select id="institute" name="institute" required>
            <option value="" disabled selected>
              Selecione uma instituição
            </option>

            <?php foreach ($institutes as $inst): ?>
              <option value="<?= $inst['id'] ?>">
                <?= htmlspecialchars($inst['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field" id="f-email">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" placeholder="voce@exemplo.com" autocomplete="email" required>
        </div>
        <div class="field" id="f-pass">
          <label for="pass">Senha</label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" placeholder="Crie uma senha"
              autocomplete="current-password" required minlength="8">

            <button type="button" id="toggle_pass" class="password-toggle" onclick="toggle()" title="Mostrar senha">
              <img id="eye-icon" src="../../uploads/icones/olhof.png" alt="Mostrar senha">
            </button>
          </div>
          <?php if (isset($_SESSION['msg'])): ?>

            <div class="session-msg <?= $_SESSION['msg'] === 'Usuário criado com sucesso.' ? 'success' : '' ?>">
              <?= htmlspecialchars($_SESSION['msg']) ?>
            </div>

            <?php
            if ($_SESSION['msg'] === 'Usuário criado com sucesso.') {
              unset($_SESSION['msg']);
              header("Refresh: 1; url=login.php");
            } else {
              unset($_SESSION['msg']);
            }
            ?>

          <?php endif; ?>
          <button class="btn btn-gradient btn-block" type="submit">Criar perfil</button>
        </div>
      </form>

      <p class="auth-foot">Já tem uma conta? <a href="login.php">Entrar</a></p>
    </div>
  </div>
  <script src="toggle.js"></script>
  <script src="theme.js"></script>
</body>

</html>