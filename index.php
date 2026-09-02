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
  <title>Cashfy — Compra e venda entre estudantes</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="MVC/view/style.css">
</head>

<body>
  <div class="page">
    <header class="site-header">
      <div class="container">
        <a href="index.php" class="brand"><span class="brand-mark"></span>CashFY</a>
        <label class="theme-switch">
          <input type="checkbox" id="theme-toggle" onchange="toggleDarkMode()">
          <span class="slider"></span>
        </label>
        <ul class="nav-links">
          <li><a href="index.php" class="active">Home</a></li>
          <li><a href="MVC/view/contact.php">Contato</a></li>
          <li><a href="MVC/view/sobre.php">Sobre nós</a></li>
        </ul>
        <?php if (isset($_SESSION['id'])): ?>
          <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3): ?>
            <div class="header-actions">
              <a href="MVC/view/perfil.php?vendedor=true" class="btn btn-gradient btn-sm">Vender aqui</a>
            </div>
          <?php else: ?>
            <div class="header-actions">
              <a href="MVC/controller/log-out.php" class="btn btn-gradient btn-sm">Fazer log-out</a>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="header-actions">
            <a href="MVC/view/login.php" class="btn btn-gradient btn-sm">Fazer log-in</a>
          </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['id'])): ?>
          <?php if (!empty($_SESSION['profile_photo'])): ?>
            <a href="MVC/view/perfil.php" class="account">
              <div class="profile-photo-icon-mother">
                <span class="account-mark"><img src="<?= $_SESSION['profile_photo'] ?>" alt="Perfil">
                </span>
              </div>
            </a>
          <?php else: ?>
            <a href="MVC/view/perfil.php" class="account">
              <div class="profile-photo-icon-mother">
                <span class="index-account-mark-child"> <?= pegarIniciais($_SESSION['name']) ?></span>
              </div>
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </header>

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
                <div class="thumb">🛍️</div>
                <div class="seller-info">
                  <p class="seller-name"><?= htmlspecialchars($seller['name']); ?></p>
                  <p class="seller-desc"><?= htmlspecialchars($seller['description'] ?? ''); ?></p>
                  <span class="stars">★★★★<span class="off">★</span></span>
                </div>
                <a class="btn btn-orange" href="MVC/view/vendedor.php?id=<?= htmlspecialchars($seller['id']); ?>">Comprar</a>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['search-users'])): ?>
          <?php foreach ($_SESSION['search-users'] as $seller): ?>
            <?php if ($seller['role_id'] == 2): ?>
              <div class="seller-card" id="seller-<?= $seller['id'] ?>">
                <div class="thumb">🛍️</div>
                <div class="seller-info">
                  <p class="seller-name"><?= htmlspecialchars($seller['name']); ?></p>
                  <p class="seller-desc"><?= htmlspecialchars($seller['description'] ?? ''); ?></p>
                  <span class="stars">★★★★<span class="off">★</span></span>
                </div>
                <a class="btn btn-orange" href="MVC/view/vendedor.php?id=<?= htmlspecialchars($seller['id']); ?>">Comprar</a>
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
  <script>
    const themeToggle = document.getElementById("theme-toggle");

    function toggleDarkMode() {
      document.body.classList.toggle("dark-mode");

      localStorage.setItem(
        "theme",
        document.body.classList.contains("dark-mode") ? "dark" : "light"
      );
    }

    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark-mode");
      themeToggle.checked = true;
    }
  </script>
  <script>
    window.addEventListener('load', function () {

      const hash = window.location.hash;

      if (!hash) {
        return;
      }

      const vendedor = document.getElementById(hash.substring(1));

      if (!vendedor) {
        return;
      }

      const rect = vendedor.getBoundingClientRect();

      const posicao =
        window.scrollY +
        rect.top -
        (window.innerHeight / 2) +
        (rect.height / 2);

      window.scrollTo({
        top: posicao,
        behavior: 'smooth'
      });

    });
  </script>
</body>

</html>