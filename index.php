<?php ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('MVC/config/init.php');
require_once('MVC/config/auth.php');
require_once('MVC/model/user.php');
require_once('MVC/model/category.php');
require_once('MVC/model/orders.php');

$users = get_users();
$categories = get_categories();

if (isset($_SESSION['id'])) {
  $user = get_user_by_id($_SESSION['id']);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Cashfy — Compra e venda entre estudantes</title>
  <link rel="icon" href="assets/img/favicon-v2.png" type="image/png" id="icon">
  <link rel="apple-touch-icon" href="assets/img/favicon-v2.png" id="apple-icon">
  <link rel="stylesheet" href="assets/fontawesome-free-7.3.1-web/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#1F7A70">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="CashFY">
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

          <li class="home <?php if (isset($user)) {
                            echo 'client';
                          } ?>">
            <a href="index.php" class="active">Home</a>
          </li>

          <li>
            <a href="MVC/view/contact.php">Contato</a>
          </li>

          <li class="about">
            <a href="MVC/view/sobre.php">Sobre nós</a>
          </li>

          <?php if (isset($user)): ?>
            <li>
              <a href="MVC/view/myOrders.php">Meus pedidos</a>
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

              <?php if (!empty($user['profile_photo'])): ?>

                <span class="account-mark">
                  <img src="<?= $user['profile_photo'] ?>" alt="Perfil">
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
            <a class="cat-card <?= $class; ?>" href="MVC/view/categoria.php?cat=<?= $category['id']; ?>">
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
                  <?php if (empty($seller['profile_photo'])): ?>
                    <span class="seller-slang-name">
                      <?= pegarIniciais($seller['name']); ?>
                    </span>
                  <?php else: ?>
                    <img src="<?= $seller['profile_photo']; ?>" alt="Foto de perfil">
                  <?php endif; ?>
                </div>

                <div class="seller-info">

                  <p class="seller-name">
                    <?= htmlspecialchars($seller['name']); ?>
                  </p>

                  <div>
                    <!-- <span class="stars">★★★★
                      <span class="off">★</span>
                    </span> -->
                  </div>
                  <p class="seller-desc">
                    <?= htmlspecialchars($seller['description'] ?? ''); ?>
                  </p>

                </div>
                <a class="btn btn-orange seller-button-card" href="MVC/view/seller.php?id=<?= htmlspecialchars($seller['id']); ?>">Ver loja</a>
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
                  <!-- <span class="stars">★★★★</span>
                  <span class="off">★</span> -->
                </div>
                <a class="btn btn-orange seller-button-card" href="MVC/view/seller.php?id=<?= htmlspecialchars($seller['id']); ?>">
                  Ver loja
                </a>
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

  <?php require __DIR__ . '/MVC/view/partials/cart_modal.php'; ?>
  <script src="assets/js/theme.js"></script>
  <script src="assets/js/menu-toggle.js"></script>
  <script src="assets/js/cookies.js"></script>
  <?php require __DIR__ . '/MVC/view/partials/order_notifier.php'; ?>
</body>

</html>