<?php session_start();
require_once('../model/user.php');
require_once('../model/products.php');
$seller = get_user_by_id($_GET['id']);
$products = get_products();

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
  <title>Vendedor — Cashfy</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="page">

    <!-- Links -->

    <header class="site-header">
      <div class="container">
        <a href="../../index.php" class="brand"><span class="brand-mark"></span> Cashfy</a>
        <ul class="nav-links">
          <li><a href="../../index.php">Home</a></li>
          <li><a href="../../index.php#contato">Contato</a></li>
          <li><a href="sobre.php">Sobre nós</a></li>
        </ul>
        <div class="header-actions">
          <a href="login.php" class="btn btn-gradient btn-sm">Vender aqui</a>
        </div>
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
                  <?= pegarIniciais($_SESSION['name']); ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="profile-meta">
            <h1 id="pf-name"><?= htmlspecialchars($seller['name']); ?></h1>
            <p id="pf-desc"><?= htmlspecialchars($seller['description']); ?></p>
            <span class="stars" id="pf-stars">★★★★<span class="off">★</span></span>
          </div>
          <a href="#" class="profile-contact" id="pf-contact-btn"><?= htmlspecialchars($seller['phone_number']); ?></a>
        </div>
      </div>

      <!-- Produtos do vendedor -->

      <div class="pill-tag">Produtos</div>
      <div class="product-grid" id="pf-products">
        <?php foreach ($products as $product): ?>
          <?php if ($product['user_id'] == $seller['id']): ?>
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
                <?= number_format((htmlspecialchars($product['price'])), 2, '.', ','); ?>
              </p>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </main>

    <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
  </div>
  <script src="return.js"></script>
  <script src="theme.js"></script>
</body>
</html>