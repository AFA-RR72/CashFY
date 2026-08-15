<?php require_once('../model/user.php');
$user = get_user_by_id($_GET['id']);

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
      <a class="back-link" href="../../index.php">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
          stroke-linecap="round">
          <line x1="19" y1="12" x2="5" y2="12" />
          <polyline points="12 19 5 12 12 5" />
        </svg>
        Voltar
      </a>

      <div class="profile-card">
        <div class="profile-banner"></div>
        <div class="profile-body">
          <div class="avatar" id="pf-avatar">A</div>
          <div class="profile-meta">
            <h1 id="pf-name"><?= htmlspecialchars($user['name']); ?></h1>
            <p id="pf-desc"><?= htmlspecialchars($user['description']); ?></p>
            <span class="stars" id="pf-stars">★★★★<span class="off">★</span></span>
          </div>
          <a href="#" class="profile-contact" id="pf-contact-btn">Contato</a>
        </div>
      </div>

      <div class="pill-tag">Produtos</div>
      <div class="product-grid" id="pf-products">
        <div class="product-card">
          <div class="product-img">Imagem</div>
          <p class="product-name">Coxinha de frango</p>
          <p class="product-desc">Feita na hora, crocante por fora</p>
          <p class="product-price">R$ 6,00</p>
        </div>
        <div class="product-card">
          <span class="badge-new">Novo!</span>
          <div class="product-img">Imagem</div>
          <p class="product-name">Marmita fitness</p>
          <p class="product-desc">Arroz, frango grelhado e legumes</p>
          <p class="product-price">R$ 18,00</p>
        </div>
      </div>
    </main>

    <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
  </div>
</body>

</html>