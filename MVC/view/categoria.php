<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categoria — Cashfy</title>
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
      <div class="crumb-row">
        <a class="back-link" href="../../index.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
            stroke-linecap="round">
            <line x1="19" y1="12" x2="5" y2="12" />
            <polyline points="12 19 5 12 12 5" />
          </svg>
          Voltar
        </a>
        <nav class="breadcrumb"><a href="../../index.php">Início</a> &gt; <a href="../../index.php">Categorias</a> &gt;
          <span id="crumb-cat">Comida</span>
        </nav>
      </div>

      <!-- Exemplo estático da categoria "Comida". No PHP, troque o
         título/ícone/quantidade conforme "$_GET['cat']" e a consulta
         no banco de dados. -->
      <div class="cat-banner">
        <div class="cat-banner-icon" id="banner-icon">🍽️</div>
        <div>
          <h1 id="banner-title">Comida</h1>
          <p id="banner-count">2 vendedores nessa categoria</p>
        </div>
      </div>

      <div id="seller-list" class="seller-list" style="margin-top:30px;">
        <div class="seller-card">
          <div class="thumb">🛍️</div>
          <div class="seller-info">
            <p class="seller-name">Ana Beatriz</p>
            <p class="seller-desc">Salgados e marmitas caseiras</p>
            <span class="stars">★★★★<span class="off">★</span></span>
          </div>
          <a class="btn btn-orange" href="vendedor.php?id=ana">Comprar</a>
        </div>
        <div class="seller-card">
          <div class="thumb">🛍️</div>
          <div class="seller-info">
            <p class="seller-name">João Pedro</p>
            <p class="seller-desc">Bolos e doces por encomenda</p>
            <span class="stars">★★★★<span class="off">★</span></span>
          </div>
          <a class="btn btn-orange" href="vendedor.php?id=joao">Comprar</a>
        </div>
      </div>
    </main>

    <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
  </div>
</body>

</html>