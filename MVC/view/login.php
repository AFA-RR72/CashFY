<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entrar — Cashfy</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="auth-page">
    <a href="../../index.php" class="auth-back">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
        stroke-linecap="round">
        <line x1="19" y1="12" x2="5" y2="12" />
        <polyline points="12 19 5 12 12 5" />
      </svg>
      Voltar
    </a>

    <div class="auth-card">
      <p class="brand"><span class="brand-mark"></span> Cashfy</p>
      <p class="auth-sub">Entrar na sua conta</p>

      <!-- action="login.php" -> o próprio Andrei conecta esse form no
         Controller de autenticação (MVC/controller) via POST -->

      <!-- Próprio Andrei aqui: to quase chorando porque são 02:43 e eu passei duas horas procurando o erro no script (era só uma única letra maiúscula que quebrava todo o código de log-in) -->
      <form id="login-form" action="../controller/login.php" method="post" novalidate>
        <div class="field" id="f-email">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" placeholder="voce@exemplo.com" autocomplete="email" required>
        </div>
        <div class="field" id="f-pass">
          <label for="pass">Senha</label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password"
              required minlength="8">
            <button type="button" id="toggle_pass" class="password-toggle" onclick="toggle()" title="Mostrar senha">
              <img id="eye-icon" src="../../uploads/icones/olhof.png" alt="Mostrar senha">
            </button>
          </div>
          <?php if (isset($_SESSION['msg'])): ?>

            <div class="session-msg <?= $_SESSION['msg'] === 'Log-in realizado com sucesso.' ? 'success' : '' ?>">
              <?= htmlspecialchars($_SESSION['msg']) ?>
            </div>


            <?php if ($_SESSION['msg'] === 'Log-in realizado com sucesso.')
            {
              unset($_SESSION['msg']);
              header("Refresh: 2; url=../../index.php");
            } else{
              unset($_SESSION['msg']);
            }
            ?>

          <?php endif; ?>
          <button class="btn btn-gradient btn-block" type="submit">Entrar</button>
        </div>
      </form>

      <p class="auth-foot">Não tem uma conta? <a href="cadastro.php">Criar</a></p>
    </div>
  </div>
  <script src="toggle.js"></script>
  <script src="theme.js"></script>
</body>

</html>