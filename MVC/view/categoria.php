<?php session_start();
require_once("../config/init.php");
require_once("../model/category.php");
require_once("../model/products.php");
require_once("../model/user.php");

if (!isset($_GET['cat']) || empty($_GET['cat']) || !check_category($_GET['cat'])) {
  echo '<script>
        history.back();
    </script>';
  exit;
}

$category = get_category_by_slug($_GET['cat']);
$products = get_products_by_category($category['id']);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categoria — Cashfy</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
  <div class="page">

    <!-- Cabeçalho -->

    <header class="site-header">
      <div class="container">
        <a href="../../index.php" class="brand">
          <span class="brand-mark"></span> CashFY
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
            <a href="../../index.php">Home</a>
          </li>
          <li>
            <a href="contact.php">Contato</a>
          </li>
          <li>
            <a href="sobre.php">Sobre nós</a>
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
        <?php if (isset($_SESSION['id'])): ?>
          <a href="perfil.php" class="account">
            <div class="profile-photo-icon-mother">

              <?php if (!empty($_SESSION['profile_photo'])): ?>

                <span class="account-mark">
                  <img src="../../<?= $_SESSION['profile_photo'] ?>" alt="Perfil">
                </span>

              <?php else: ?>
                <span class="index-account-mark-child">
                  <?= pegarIniciais($_SESSION['name']); ?>
                </span>
              <?php endif; ?>
            </div>
          </a>
        <?php else: ?>
          <div class="header-actions">
            <a href="login.php" class="btn btn-gradient btn-sm">
              Fazer log-in
            </a>
          </div>
        <?php endif; ?>
      </div>
    </header>

    <!-- Links -->

    <main class="container" style="flex:1;">
      <div class="crumb-row">
        <a class="back-link" href="../../index.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
            stroke-linecap="round">
            <line x1="19" y1="12" x2="5" y2="12" />
            <polyline points="12 19 5 12 12 5" />
          </svg>
          Voltar
        </a>
        <nav class="breadcrumb"><a href="../../index.php">Início</a> &gt; <a
            href="../../index.php#categories">Categorias</a> &gt;
          <span id="crumb-cat"><?= $category['name']; ?></span>
        </nav>
      </div>

      <!-- Categoria -->

      <?php $classes = ['cat-blue', 'cat-gold', 'cat-orange', 'cat-green']; ?>

      <div class="cat-banner <?= $classes[($category['id'] - 1) % 4]; ?>">
        <div class="cat-banner-icon" id="banner-icon"><?= $category['icon']; ?></div>
        <div>
          <h1 id="banner-title"><?= $category['name']; ?></h1>
          <p id="banner-count">
            <?= count($products) ?> produto<?php if (count($products) != 1) {
                 echo 's';
               }
               ?>
            nessa categoria
          </p>
        </div>
      </div>

      <!-- Produtos -->

      <div class="seller-products">
        <div class="product-grid" id="pf-products">
          <?php foreach ($products as $product): ?>
            <?php if ($product['category_id'] == $category['id']): ?>
              <?php $seller = get_user_by_id($product['user_id']) ?>
              <div class="product-card">
                <span class="badge-new">Novo!</span>
                <div class="product-img"><img src="<?= "../../" . $product['product_photo'] ?>" alt="Image">
                </div>
                <p class="product-name">
                  <?= htmlspecialchars($product['name']) ?? ''; ?>
                </p>
                <a href="seller.php?id=<?= $product['user_id']; ?>" class="product-card-seller">
                  <?= htmlspecialchars($seller['name']) ?? ''; ?>
                </a>

                <p class="product-desc">
                  <?= htmlspecialchars($product['description']) ?? ''; ?>
                </p>
                <p class="product-price">
                  <?= 'R$ ' . htmlspecialchars(number_format(($product['price'] ?? 0), 2, ',', '.')); ?>
                </p>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </main>

    <!-- Rodapé -->

    <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
  </div>
  <script src="../../assets/js/return.js"></script>
  <script src="../../assets/js/theme.js"></script>
  <script src="../../assets/js/menu-toggle.js"></script>
</body>

</html>