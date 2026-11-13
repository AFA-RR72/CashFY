<?php
require_once("../config/init.php");
require_once("../config/auth.php");
require_once("../config/cart_return.php");
require_once("../model/category.php");
require_once("../model/products.php");
require_once("../model/user.php");

$category_param = trim((string) ($_GET['cat'] ?? ''));

if ($category_param === '') {
  header('Location: ../../index.php');
  exit;
}

if (ctype_digit($category_param)) {
  $category = get_category_by_id((int) $category_param);
} else {
  $category = get_category_by_slug($category_param);
}

if (!$category) {
  header('Location: ../../index.php');
  exit;
}

$products = get_products_by_category((int) $category['id']);
$product_modal = null;

if (isset($_GET['product']) && ctype_digit((string) $_GET['product'])) {
  $requested_product = get_product_by_id((int) $_GET['product']);

  if ($requested_product && (int) $requested_product['category_id'] === (int) $category['id']) {
    $product_modal = $requested_product;
  }
}

if (isset($_SESSION['id'])) {
  $user = get_user_by_id($_SESSION['id']);
}


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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Categoria — Cashfy</title>
  <link rel="stylesheet" href="../../assets/fontawesome-free-7.3.1-web/css/all.min.css">
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

          <li class="home <?php if(isset($user) && $user['role_id'] == 3){ echo 'client'; }?>">
            <a href="../../index.php">Home</a>
          </li>

          <li>
            <a href="contact.php">Contato</a>
          </li>

          <li>
            <a href="sobre.php">Sobre nós</a>
          </li>

          <?php if (isset($user) && $user['role_id'] == 3): ?>
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
        <?php if (isset($_SESSION['id'])): ?>
          <a href="perfil.php" class="account">
            <div class="profile-photo-icon-mother">

              <?php if (!empty($user['profile_photo'])): ?>

                <span class="account-mark">
                  <img src="../../<?= $user['profile_photo'] ?>" alt="Perfil">
                </span>

              <?php elseif (isset($user)) : ?>
                <span class="index-account-mark-child">
                  <?= pegarIniciais($user['name']); ?>
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
              <article class="product-card">
                <span class="badge-new">Novo!</span>
                <div class="product-img">
                  <img src="<?= htmlspecialchars('../../' . $product['product_photo']); ?>"
                    alt="<?= htmlspecialchars($product['name']); ?>">
                </div>

                <p class="product-name">
                  <?= htmlspecialchars($product['name']); ?>
                </p>

                <a href="seller.php?id=<?= (int) $product['user_id']; ?>" class="product-card-seller">
                  <?= htmlspecialchars($seller['name'] ?? 'Vendedor'); ?>
                </a>

                <p class="product-desc">
                  <?= htmlspecialchars($product['description']); ?>
                </p>

                <p class="product-price">
                  <?= 'R$ ' . number_format((float) $product['price'], 2, ',', '.'); ?>
                </p>

                <a class="btn btn-gradient btn-sm category-add-button"
                  href="?cat=<?= rawurlencode((string) $category['id']); ?>&amp;product=<?= (int) $product['id']; ?>">
                  <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
                  Adicionar ao carrinho
                </a>
              </article>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </main>

    <?php if ($product_modal): ?>
      <dialog id="product-modal" open aria-labelledby="category-product-title">
        <a class="product-modal-back"
          href="?cat=<?= rawurlencode((string) $category['id']); ?>"
          aria-label="Fechar detalhes do produto">
          <i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
        </a>

        <h2 id="category-product-title"><?= htmlspecialchars($product_modal['name']); ?></h2>

        <div class="cart-form">
          <div>
            <img
              src="<?= htmlspecialchars('../../' . $product_modal['product_photo']); ?>"
              alt="<?= htmlspecialchars($product_modal['name']); ?>">
          </div>

          <div>
            <p class="product-desc"><?= htmlspecialchars($product_modal['description']); ?></p>
            <p class="product-price">
              R$ <?= number_format((float) $product_modal['price'], 2, ',', '.'); ?>
            </p>

            <form action="../controller/cart.php" method="post">
              <input type="hidden" name="product_id" value="<?= (int) $product_modal['id']; ?>">
              <input type="hidden" name="return" value="<?= htmlspecialchars(cart_current_page(), ENT_QUOTES); ?>">

              <div class="cart-quantity">
                <label for="category-cart-quantity">Quantidade</label>
                <div class="input">
                  <button type="button" class="quantity-decrease" aria-label="Diminuir quantidade">−</button>
                  <input type="number" name="quantity" id="category-cart-quantity" value="1" min="1" step="1" required>
                  <button type="button" class="quantity-increase" aria-label="Aumentar quantidade">+</button>
                </div>
              </div>

              <button type="submit" class="btn btn-gradient">
                <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
                Adicionar ao carrinho
              </button>
            </form>
          </div>
        </div>
      </dialog>
    <?php endif; ?>

    <?php require __DIR__ . '/partials/cart_modal.php'; ?>

    <!-- Rodapé -->

    <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
  </div>
  <script src="../../assets/js/return.js"></script>
  <script src="../../assets/js/theme.js"></script>
  <script src="../../assets/js/menu-toggle.js"></script>
  <script>
    (() => {
      const quantityInput = document.getElementById('category-cart-quantity');
      if (!quantityInput) return;

      document.querySelector('.quantity-decrease')?.addEventListener('click', () => {
        quantityInput.value = Math.max(1, (Number.parseInt(quantityInput.value, 10) || 1) - 1);
      });

      document.querySelector('.quantity-increase')?.addEventListener('click', () => {
        quantityInput.value = (Number.parseInt(quantityInput.value, 10) || 1) + 1;
      });

      quantityInput.addEventListener('change', () => {
        quantityInput.value = Math.max(1, Number.parseInt(quantityInput.value, 10) || 1);
      });
    })();
  </script>
  <?php require __DIR__ . '/partials/order_notifier.php'; ?>
</body>

</html>