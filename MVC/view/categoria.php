<?php session_start();
require_once("../model/category.php");
require_once("../model/products.php");

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
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="page">

    <!-- Cabeçalho -->

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
          <p id="banner-count"><?= count($products) ?> produto<?php if (count($products) != 1) {echo 's';} ?> nessa categoria</p>
        </div>
      </div>

      <!-- Produtos -->

      <div class="seller-products">
        <div class="product-grid" id="pf-products">
          <?php foreach ($products as $product): ?>
            <?php if ($product['category_id'] == $category['id']): ?>
              <div class="product-card">
                <span class="badge-new">Novo!</span>
                <div class="product-img"><img src="<?= "../../" . $product['product_photo'] ?>" alt="Image"></div>
                <p class="product-name"><?= htmlspecialchars($product['name']) ?? ''; ?></p>
                <p class="product-desc"><?= htmlspecialchars($product['description']) ?? ''; ?></p>
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
  <script src="theme.js"></script>
</body>

</html>