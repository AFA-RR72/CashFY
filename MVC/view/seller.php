<?php session_start();
require_once('../model/user.php');
require_once('../model/products.php');
$seller = get_user_by_id($_GET['id']);
$products = get_products();


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
  <title>Vendedor — Cashfy</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
  <div class="page">

    <!-- Cabeçalho -->

    <header class="site-header">
      <div class="container">
        <a href="../../index.php" class="brand">
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
        </ul>


        <?php if (!isset($_SESSION['id'])): ?>

          <!-- DESLOGADO: LOGIN FICA FORA DO MENU -->
          <div class="header-actions">
            <a href="login.php" class="btn btn-gradient btn-sm">
              Fazer log-in
            </a>
          </div>

        <?php else: ?>

          <!-- LOGADO: FOTO FICA FORA DO MENU -->
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
        <?php endif; ?>
      </div>
    </header>

    <main class="container" style="flex:1;">
      <a class="back-link" href="#" onclick="voltarPagina(event)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
          stroke-linecap="round">
          <line x1="19" y1="12" x2="5" y2="12" />
          <polyline points="12 19 5 12 12 5" />
        </svg>
        Voltar
      </a>

      <!-- Perfil do vendedror -->

      <div class="profile-card">
        <div class="profile-banner"></div>
        <div class="profile-body">
          <div class="avatar" id="pf-avatar">
            <?php if (!empty($seller['profile_photo'])): ?>
              <div class="avatar" id="pf-avatar">
                <img src="../../<?= $seller['profile_photo']; ?>" alt="Perfil">
              </div>
            <?php endif; ?>
            <?php if (empty($seller['profile_photo'])): ?>
              <div class="avatar" id="pf-avatar">
                <?= pegarIniciais($seller['name']); ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="profile-meta">
            <h1 id="pf-name"><?= htmlspecialchars($seller['name']); ?></h1>
            <p id="pf-desc"><?= htmlspecialchars($seller['description']); ?></p>
            <span class="stars" id="pf-stars">★★★★<span class="off">★</span></span>
          </div>
          <a href="https://wa.me/<?= preg_replace('/\D/', '', $seller['phone_number']); ?>" class="profile-contact"
            id="pf-contact-btn"><?= htmlspecialchars($seller['phone_number']); ?></a>
        </div>
      </div>

      <!-- Carrinho -->

      <?php if (isset($_GET['product'])): ?>
        <?php
        $params = $_GET;
        $url = http_build_query($params);

        $product = get_product_by_id($_GET['product']);
        ?>
        <dialog id="cart" open>
          <h2><?= $product['name'] ?></h2>
          <?php unset($params['product']);
          $url = http_build_query($params); ?>
          <a href="seller.php?<?= htmlspecialchars($url); ?>"><i class="fa-solid fa-arrow-left-long"></i></a>
              <div class="cart-form">
                <div>
                  <img src="../../<?= $product['product_photo'] ?>" alt="">
                </div>
                <div>
                  <form action="../controller/cart.php?<?= htmlspecialchars($url); ?>" method="post">
                    <div class="cart-quantity">
                      <label for="quantity">Quantidade</label>
                      <div class="input">
                        <button type="button" onclick="less()">-</button>
                        <input type="number" name="quantity" id="cart-quantity" value="1">
                        <button type="button" onclick="more()">+</button>
                      </div>
                    </div>
                    <input type="number" name="product_id" value="<?= $product['id']; ?>" hidden>
                    <button type="submit" class="btn btn-gradient">Adicionar</button>
                  </form>
                </div>

        </dialog>
      <?php endif; ?>

      <!-- Produtos do vendedor -->

      <div class="pill-tag">Produtos</div>
      <div class="product-grid" id="pf-products">
        <?php foreach ($products as $product): ?>
          <?php if ($product['user_id'] == $seller['id']): ?>
            <?php
            $params = $_GET;
            $params['product'] = $product['id'];

            $url = '?' . http_build_query($params);
            ?>
            <a href="<?= htmlspecialchars($url); ?>">
              <div class="product-card">
                <span class="badge-new">Novo!</span>
                <div class="product-img">
                  <img src="../../<?= $product['product_photo']; ?>" alt="Preview">
                </div>
                <p class="product-name">
                  <?= htmlspecialchars($product['name']); ?>
                </p>
                <p class="product-desc">
                  <?= htmlspecialchars($product['description']); ?>
                </p>
                <p class="product-price">R$
                  <?= number_format($product['price'], 2, '.', ','); ?>
                </p>
              </div>
            </a>

          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </main>

    <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
  </div>
  <script src="../../assets/js/return.js"></script>
  <script src="../../assets/js/theme.js"></script>
  <script src="../../assets/js/menu-toggle.js"></script>
  <script>
    function more() {
      const inputQuantity = document.getElementById('cart-quantity');
      inputQuantity.value = Number(inputQuantity.value) + 1;
    }

    function less() {
      const inputQuantity = document.getElementById('cart-quantity');

      if (Number(inputQuantity.value) > 1) {
        inputQuantity.value = Number(inputQuantity.value) - 1;
      }
    }
  </script>
</body>

</html>