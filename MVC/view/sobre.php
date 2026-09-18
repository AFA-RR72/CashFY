<?php session_start();

function pegarIniciais(string $frase, array $ignorar = ['de', 'e', 'do', 'da', 'dos', 'das', 'o', 'a', 'com', 'em'])
{
  $palavras = preg_split('/\s+/', trim($frase));
  $palavrasValidas = [];

  foreach ($palavras as $palavra) {
    $palavraMinuscula = mb_strtolower($palavra, 'UTF-8');

    if (empty($palavraMinuscula) || in_array($palavraMinuscula, $ignorar)) {
      continue;
    }

    $palavrasValidas[] = $palavra;
  }

  $quantidade = count($palavrasValidas);

  if ($quantidade === 0) {
    return '';
  }

  if ($quantidade === 1) {
    return mb_strtoupper(
      mb_substr($palavrasValidas[0], 0, 1, 'UTF-8'),
      'UTF-8'
    );
  }

  if ($quantidade === 2) {
    $primeira = mb_substr($palavrasValidas[0], 0, 1, 'UTF-8');
    $ultima = mb_substr($palavrasValidas[1], 0, 1, 'UTF-8');

    return mb_strtoupper($primeira . $ultima, 'UTF-8');
  }

  $primeira = mb_substr($palavrasValidas[0], 0, 1, 'UTF-8');
  $segunda = mb_substr($palavrasValidas[1], 0, 1, 'UTF-8');
  $ultima = mb_substr($palavrasValidas[$quantidade - 1], 0, 1, 'UTF-8');

  return mb_strtoupper($primeira . $segunda . $ultima, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sobre nós — Cashfy</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
  <div class="page">

    <header class="site-header">
      <div class="container">
        <a href="../../index.php" class="brand"><span class="brand-mark"></span> CashFY</a>
        <ul class="nav-links">
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
          <li class="home">
            <a href="../../index.php">Home</a>
          </li>
          <li>
            <a href="../../index.php#contato">Contato</a>
          </li>
          <li>
            <a href="sobre.php" class="active">Sobre nós</a>
          </li>
        </ul>
        <?php if (isset($_SESSION['id'])): ?>
          <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3): ?>
            <div class="header-actions">
              <a href="perfil.php?vendedor=true" class="btn btn-gradient btn-sm">Vender aqui</a>
            </div>
          <?php else: ?>
            <div class="header-actions">
              <a href="../controller/log-out.php" class="btn btn-gradient btn-sm">Fazer log-out</a>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="header-actions">
            <a href="login.php" class="btn btn-gradient btn-sm">Fazer log-in</a>
          </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['id'])): ?>
          <?php if (!empty($_SESSION['profile_photo'])): ?>
            <a href="perfil.php" class="account">
              <div class="profile-photo-icon-mother">
                <span class="account-mark"><img src="../../<?= $_SESSION['profile_photo'] ?>" alt="Perfil">
                </span>
              </div>
            </a>
          <?php else: ?>
            <a href="perfil.php" class="account">
              <div class="profile-photo-icon-mother">
                <span class="index-account-mark-child"> <?= pegarIniciais($_SESSION['name']) ?></span>
              </div>
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </header>

    <main class="container" style="flex:1;">

      <div class="about-hero">
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
      Cashfy — feito por estudantes, para estudantes. &nbsp;·&nbsp; <span id="contato">contato@cashfy.com</span>
    </footer>
  </div>
  <script src="../../assets/js/theme.js"></script>
  <script src="../../assets/js/menu-toggle.js"></script>
</body>

</html>