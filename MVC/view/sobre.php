<?php session_start();

function pegarIniciais(string $frase, array $ignorar = ['de', 'e', 'do', 'da', 'dos', 'das', 'o', 'a', 'com', 'em'])
{
  $palavras = preg_split('/\s+/', trim($frase));
  $iniciais = '';

  foreach ($palavras as $palavra) {
    $palavraMinuscula = mb_strtolower($palavra, 'UTF-8');

    if (in_array($palavraMinuscula, $ignorar) || empty($palavraMinuscula)) {
      continue;
    }

    $iniciais .= mb_substr($palavra, 0, 1, 'UTF-8');
  }

  return mb_strtoupper($iniciais, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sobre nós — Cashfy</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="page">

    <header class="site-header">
      <div class="container">
        <a href="../../index.php" class="brand"><span class="brand-mark"></span> Cashfy</a>
        <ul class="nav-links">
          <li><a href="../../index.php">Home</a></li>
          <li><a href="../../index.php#contato">Contato</a></li>
          <li><a href="sobre.php" class="active">Sobre nós</a></li>
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
  <script src="theme.js"></script>
</body>

</html>