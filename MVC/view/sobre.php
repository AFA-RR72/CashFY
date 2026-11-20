<?php
require_once('../config/auth.php');

if (isset($_SESSION['id'])) {
  $user = get_user_by_id($_SESSION['id']);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Sobre nós — Cashfy</title>
  <link rel="icon" href="../../assets/img/favicon.png" type="image/png">
  <link rel="stylesheet" href="../../assets/fontawesome-free-7.3.1-web/css/all.min.css">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="manifest" href="../../manifest.json">
  <meta name="theme-color" content="#1F7A70">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="CashFY">
  <link rel="apple-touch-icon" href="../../assets/img/favicon.png">
</head>

<body>
  <div class="page">

    <header class="site-header">

      <div class="container">

        <a href="../../index.php" class="brand">
          <span class="brand-mark"></span>
          CashFY
        </a>

        <div class="theme-switch-div desktop-theme">

          <label class="theme-switch">
            <input type="checkbox" id="theme-toggle-desktop">
            <span class="slider"></span>
          </label>

        </div>

        <button class="menu-btn" id="menuToggle" aria-label="Abrir menu">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
        </button>

        <ul class="nav-links">

          <li class="home <?php if (isset($user)) {
                            echo 'client';
                          } ?>">
            <a href="../../index.php">Home</a>
          </li>

          <li>
            <a href="contact.php">Contato</a>
          </li>

          <li>
            <a href="" class="active">Sobre nós</a>
          </li>

          <?php if (isset($user)): ?>
            <li>
              <a href="myOrders.php">Meus pedidos</a>
            </li>
          <?php endif; ?>

          <li class="mobile-theme">

            <div class="theme-switch-div">

              <label class="theme-switch">
                <input type="checkbox" id="theme-toggle-mobile">
                <span class="slider"></span>
              </label>

            </div>

          </li>

        </ul>

        <?php if (!isset($_SESSION['id'])): ?>

          <!-- DESLOGADO -->

          <div class="header-actions">

            <a href="login.php" class="btn btn-gradient btn-sm">
              Fazer log-in
            </a>

          </div>

        <?php else: ?>

          <!-- LOGADO -->

          <a href="perfil.php" class="account">

            <div class="profile-photo-icon-mother">

              <?php if (!empty($user['profile_photo'])): ?>

                <span class="account-mark">

                  <img
                    src="../../<?= $user['profile_photo'] ?>"
                    alt="Perfil">

                </span>

              <?php elseif (isset($user)): ?>

                <span class="index-account-mark-child">
                  <?= pegarIniciais($user['name']); ?>
                </span>

              <?php endif; ?>

            </div>

          </a>

        <?php endif; ?>

      </div>

    </header>

    <main class="container" style="flex:1;">

      <div class="page-header">
        <h1>Sobre nós</h1>
        <p>Conheça quem faz o Cashfy acontecer — um projeto feito por estudantes, para estudantes.</p>
      </div>

      <h2 class="section-title">Nossa equipe</h2>

      <div class="team-grid">

        <div class="team-card">
          <div class="team-photo"><img src="../../uploads/icones/andrei.jpg" alt=""></div>
          <p class="team-name">Andrei Freire</p>
          <p class="team-role">back-end e desenvolvedor</p>
          <p class="team-bio placeholder">Andrei é responsável pela inteligência e lógica do site.</p>
        </div>

        <div class="team-card">
          <div class="team-photo"><img src="../../uploads/icones/eu.jpeg" alt=""></div>
          <p class="team-name">Carlos Henrique</p>
          <p class="team-role">front-end e desenvolvedor</p>
          <p class="team-bio placeholder">Carlos é focado na interface e interação do usuario, proporcionando
            experiências</p>
        </div>

        <div class="team-card">
          <div class="team-photo"><img src="../../uploads/icones/maria.jpg" alt=""></div>
          <p class="team-name">Maria Lara</p>
          <p class="team-role">web designer</p>
          <p class="team-bio placeholder">Maria cuida da identidade visual e do layout inicial</p>
        </div>

        <div class="team-card">
          <div class="team-photo"><img src="../../uploads/icones/larissa.jpg" alt=""></div>
          <p class="team-name">Larissa dos Santos</p>
          <p class="team-role">testadora e banco de dados</p>
          <p class="team-bio placeholder">Larissa é responsável pelos testes e pela segurança das informações</p>
        </div>

        <div class="team-card">
          <div class="team-photo"><img src="../../uploads/icones/sofia.jpeg" alt=""></div>
          <p class="team-name">Sofia Hage</p>
          <p class="team-role">líder</p>
          <p class="team-bio placeholder">Sofia é resposável pela gestao e organização</p>
        </div>

      </div>

    </main>

    <footer class="site-footer">
      Cashfy — feito por estudantes, para estudantes &nbsp;·&nbsp;
      <span id="contato">cashfy@gmail.com</span> &nbsp;·&nbsp;
      <a href="tos.php">Termos de uso</a> &nbsp;·&nbsp;
      <a href="pp.php">Políticas de privacidade</a>
    </footer>

    <div id="cookie-banner">
      <h3>Usamos cookies</h3>

      <p>
        O CashFY utiliza cookies para manter sua sessão, lembrar algumas de suas
        preferências e garantir o funcionamento correto do site.
      </p>

      <p>
        Os cookies ajudam, por exemplo, a manter você conectado e a preservar
        determinadas configurações enquanto navega pelo CashFY.
      </p>

      <p>
        Ao continuar navegando, você concorda com o uso desses cookies.
      </p>

      <button id="cookie-accept">Aceitar</button>
      <button id="cookie-reject">Sair</button>
    </div>
  </div>
  <?php require __DIR__ . '/partials/cart_modal.php'; ?>
  <?php require __DIR__ . '/partials/order_notifier.php'; ?>
  <script src="../../assets/js/theme.js"></script>
  <script src="../../assets/js/cookies.js"></script>
  <script src="../../assets/js/menu-toggle.js"></script>
</body>

</html>