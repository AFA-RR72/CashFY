<?php ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once('MVC/config/init.php');
require_once('MVC/model/user.php');
require_once('MVC/model/category.php');

$users = get_users();
$categories = get_categories();

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
  <title>Cashfy — Compra e venda entre estudantes</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <div class="page">

    <!-- Cabeçalho -->

    <header class="site-header">
      <div class="container">
        <a href="index.php" class="brand">
          <span class="brand-mark"></span>CashFY
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
          <li class="home">
            <a href="index.php" class="active">Home</a>
          </li>
          <li>
            <a href="MVC/view/contact.php">Contato</a>
          </li>
          <li class="about">
            <a href="MVC/view/sobre.php">Sobre nós</a>
          </li>
          <li class="mobile-theme">
            <div class="theme-switch-div">
              <label class="theme-switch">
                <input type="checkbox" id="theme-toggle-mobile">
                <span class="slider"></span>
              </label>
            </div>
          </li>

          <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3): ?>

            <!-- Cliente -->
            <li class="mobile-action">
              <a href="MVC/view/perfil.php?vendedor=true">
                Vender aqui
              </a>
            </li>

          <?php endif; ?>
        </ul>


        <?php if (!isset($_SESSION['id'])): ?>

          <!-- DESLOGADO: LOGIN FICA FORA DO MENU -->
          <div class="header-actions">
            <a href="MVC/view/login.php" class="btn btn-gradient btn-sm">
              Fazer log-in
            </a>
          </div>

        <?php else: ?>

          <!-- LOGADO: FOTO FICA FORA DO MENU -->
          <a href="MVC/view/perfil.php" class="account">
            <div class="profile-photo-icon-mother">

              <?php if (!empty($_SESSION['profile_photo'])): ?>

                <span class="account-mark">
                  <img src="<?= $_SESSION['profile_photo'] ?>" alt="Perfil">
                </span>

              <?php else: ?>
                <span class="index-account-mark-child">
                  <?= pegarIniciais($_SESSION['name']); ?>
                </span>
              <?php endif; ?>
            </div>
          </a>
        <?php endif; ?>
      </div>
    </header>

    <!-- Barra de pesquisa -->

    <main class="container" style="flex:1;">

      <div class="search-wrap">
        <form method="post" class="search-box" action="MVC/controller/search.php" role="search">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
            stroke-linecap="round">
            <circle cx="11" cy="11" r="7" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
          </svg>
          <input type="text" name="search" placeholder="Pesquisar por vendedores ou categorias...">
        </form>
      </div>

      <!-- As coisa de categoria aqui -->

      <h2 class="section-title">Categorias</h2>
      <div class="cat-grid" id="categories">
        <?php
        $classes = ['cat-blue', 'cat-gold', 'cat-orange', 'cat-green'];
        ?>
        <?php if (!isset($_SESSION['search-categories'])): ?>
          <?php foreach ($categories as $i => $category): ?>
            <?php $class = $classes[$i % count($classes)]; ?>
            <a class="cat-card <?= $class; ?>" href="MVC/view/categoria.php?cat=<?= $category['slug']; ?>">
              <div class="cat-icon "><?= $category['icon']; ?></div>
              <span class="cat-name"><?= $category['name']; ?></span>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- Categoria pesquisada aqui -->

        <?php if (isset($_SESSION['search-categories'])): ?>
          <?php foreach ($_SESSION['search-categories'] as $category): ?>
            <a class="cat-card <?= $classes[($category['id'] - 1) % 4]; ?>" id="<?= $category['id']; ?>"
              href="MVC/view/categoria.php?cat=<?= $category['slug']; ?>">
              <div class="cat-icon "><?= $category['icon']; ?></div>
              <span class="cat-name"><?= $category['name']; ?></span>
            </a>
          <?php endforeach; ?>
          <?php unset($_SESSION['search-categories']); ?>
        <?php endif; ?>
      </div>

      <!-- Aqui é o bagulho de vendedores -->

      <h2 class="section-title">Vendedores</h2>
      <div class="seller-list">
        <?php if (!isset($_SESSION['search-users'])): ?>
          <?php foreach ($users as $seller): ?>
            <?php if ($seller['role_id'] == 2): ?>
              <div class="seller-card">
                <div class="thumb">
                  <img src="<?= $seller['profile_photo']; ?>" alt="Foto de perfil">
                </div>

                <div class="seller-info">

                  <p class="seller-name">
                    <?= htmlspecialchars($seller['name']); ?>
                  </p>

                  <div>
                    <span class="stars">★★★★
                      <span class="off">★</span>
                    </span>
                  </div>
                  <p class="seller-desc">
                    <?= htmlspecialchars($seller['description'] ?? ''); ?>
                  </p>

                </div>
                <a class="btn btn-orange" href="MVC/view/seller.php?id=<?= htmlspecialchars($seller['id']); ?>">Comprar</a>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['search-users'])): ?>
          <?php foreach ($_SESSION['search-users'] as $seller): ?>
            <?php if ($seller['role_id'] == 2): ?>
              <div class="seller-card" id="seller-<?= $seller['id'] ?>">
                <div class="thumb">
                  <img src="<?= $seller['profile_photo']; ?>" alt="foto de perfil">
                </div>
                <div class="seller-info">

                  <p class="seller-name">
                    <?= htmlspecialchars($seller['name']); ?>
                  </p>
                  <p class="seller-desc">
                    <?= htmlspecialchars($seller['description'] ?? ''); ?>
                  </p>
                  <span class="stars">★★★★</span>
                  <span class="off">★</span>
                </div>
                <a class="btn btn-orange" href="MVC/view/seller.php?id=<?= htmlspecialchars($seller['id']);
                ?>">Comprar</a>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php unset($_SESSION['search-users']); ?>
        <?php endif; ?>
      </div>
    </main>

    <!-- Aqui já é o rodapé -->

    <footer class="site-footer">
      Cashfy — feito por estudantes, para estudantes &nbsp;·&nbsp;
      <span id="contato">cashfy@gmail.com</span> &nbsp;·&nbsp;
      <a href="MVC/view/tos.php">Termos de uso</a> &nbsp;·&nbsp;
      <a href="MVC/view/pp.php">Políticas de privacidade</a>
    </footer>
  </div>
  <script src="assets/js/theme.js"></script>
  <script src="assets/js/menu-toggle.js"></script>
</body>

</html>