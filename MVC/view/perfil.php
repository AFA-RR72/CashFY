<?php session_start();
require_once('../config/init.php');
require_once('../model/user.php');
require_once('../model/products.php');

$user = get_user_by_id($_SESSION['id']);

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
    <title>Meu perfil — Cashfy</title>
    <link rel="stylesheet" href="style.css">
</head>
<!-- Eu meio que dei ctrl+c ctrl+v no dashboard, porque é a mesma merda basicamente. vou só mudar uns nomes e class -->

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
                    <!-- "Sair" ainda é só um link. Quando o Andrei fizer a sessão em PHP,
             troca por uma action que dá destroy na sessão. 
             
             -Andrei: Eu sei como funciona, Carlos. ;-;

             -->
                    <a href="../controller/log-out.php" class="btn btn-gradient btn-sm">Sair</a>
                </div>
            </div>
        </header>

        <!-- radios escondidos que controlam as abas, sem JS -->

        <main class="container" style="flex:1;">

            <div class="profile-card">
                <div class="profile-banner"></div>
                <div class="profile-body">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <a href="perfil_photo.php" title="Atualizar foto">
                            <div class="avatar" id="pf-avatar">
                                <img src="../../<?= $user['profile_photo']; ?>" alt="Perfil">
                            </div>
                            <?php if (isset($_SESSION['msg'])): ?>
                                <div id="msg">
                                    <?php echo ($_SESSION['msg']);
                                    unset($_SESSION['msg']);
                                    ?>
                                </div>
                                <script>
                                    setTimeout(() => {
                                        document.getElementById("msg").style.display = "none";
                                    }, 3000);
                                </script>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <div class="avatar" id="pf-avatar"><a href="perfil_photo.php" title="Adicionar foto">
                                <?= pegarIniciais($_SESSION['name']) ?></a>
                        </div>
                        <?php if (isset($_SESSION['msg'])): ?>
                            <div id="msg">
                                <?php echo ($_SESSION['msg']);
                                unset($_SESSION['msg']);
                                ?>
                            </div>
                            <script>
                                setTimeout(() => {
                                    document.getElementById("msg").style.display = "none";
                                }, 3000);
                            </script>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="profile-meta">
                        <h1 id="pf-name"><?= htmlspecialchars($user['name']) ?></h1>
                        <p id="pf-desc"><?php if ($user['role_id'] === 2) ?></p>
                    </div>
                    <?php if (isset($user['phone_number'])): ?>
                        <a href="#" class="profile-contact"
                            id="add-contact-btn"><?= htmlspecialchars($user['phone_number']); ?></a>
                    <?php endif; ?>
                </div>
                <?php if (isset($_GET['vendedor']) && $_GET['vendedor'] == true): ?>
                    <div class="be-seller-form">
                        <div class="titulo-form-vendedor">Atualize seu perfil para tornar-se um vendedor <span
                                class="cashfy">CashFY</span></div>
                        <form action="../controller/seja_vendedor.php" method="post" novalidate>
                            <div class="field">
                                <label for="phone_number">Contato</label>
                                <input type="tel" name="phone_number" id="phone_number" placeholder="(00) 0 0000-0000"
                                    pattern="[0-9]{10,11}" required>
                            </div>
                            <div class="field">
                                <label for="description">Descrição</label>
                                <textarea name="description" id="description" class="description"
                                    placeholder="min. 20 letras." required></textarea>
                            </div>
                            <div class="field">
                                <label for="pass">Senha</label>
                                <div class="password-wrapper">
                                    <input type="password" id="password" name="password" placeholder="••••••••"
                                        autocomplete="current-password" required minlength="8">
                                    <button type="button" id="toggle_pass" class="password-toggle" onclick="toggle()"
                                        title="Mostrar senha">
                                        <img id="eye-icon" src="../../uploads/icones/olhof.png" alt="Mostrar senha">
                                    </button>
                                </div>
                            </div>
                            <?php if (isset($_SESSION['msg-form'])): ?>
                                <div
                                    class="session-msg <?= $_SESSION['msg-form'] === 'Perfil atualizado com sucesso.' ? 'success' : ''; ?>">
                                    <?= htmlspecialchars($_SESSION['msg-form']); ?>
                                    <?php unset($_SESSION['msg-form']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['msg-form']) && $_SESSION['msg-form'] == "Perfil atualizado com sucesso."){ unset($_GET['vendedor']); } ?>
                            <div class="field">
                                <button class="btn btn-gradient btn-block" type="submit">Atualizar perfil</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                <?php if (isset($user['role_id']) && $user['role_id'] == 2): ?>
                    <?php $products = get_products(); ?>
                    <div class="main-seller">
                        <div class="menu_details" >
                            <div class="menu_details_options">
                                <a href="">Produtos</a>
                            </div>
                            <div class="menu_details_options">
                                <a href="">Diário</a>
                            </div>
                            <div class="menu_details_options">
                                <a href="">Rendimento</a>
                            </div>
                            <div class="menu_details_options">
                                <a href="">Perfil</a>
                            </div>
                        </div>
                        <a href="new_product.php"><div class="pill-tag-perfil">Novo Produto</div></a>
                        <div class="seller-products">
                            <div class="product-grid" id="pf-products">
                                <?php foreach ($products as $product): ?>
                                    <?php if ($product['user_id'] == $user['id']): ?>
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
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== PRODUTOS ===== -->
            <section class="tab-panel" id="tab-produtos">
                <a class="btn btn-orange" href="produto-form.php">+ Adicionar produtos</a>
                <div class="product-grid" id="my-products" style="margin-top:20px;">

                    <div class="product-card">
                        <div class="product-img">Imagem</div>
                        <p class="product-name">Coxinha de frango</p>
                        <p class="product-desc">Feita na hora, crocante por fora</p>
                        <div class="product-row-actions">
                            <p class="product-price" style="margin:0;">R$ 6,00</p>
                            <div class="product-actions">
                                <a class="icon-btn" title="Editar" href="produto-form.php?id=1">✎</a>
                                <!-- ligue esse form num controller de exclusão em MVC/controller -->
                                <form method="post" action="excluir-produto.php" style="display:inline;">
                                    <input type="hidden" name="id" value="1">
                                    <button class="icon-btn danger" title="Excluir" type="submit">🗑</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ===== DIÁRIO ===== -->
            <section class="tab-panel" id="tab-diario">
                <div class="daily-card">
                    <h3>+ Registrar vendas</h3>
                    <!-- ligue este form num controller de vendas em MVC/controller -->
                    <form id="sale-form" action="registrar-venda.php" method="post">
                        <div class="field-row">
                            <div class="field">
                                <label for="sale-product">Produto</label>
                                <select id="sale-product" name="produto_id">
                                    <option value="1">Coxinha de frango</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="sale-qty">Quantidade</label>
                                <input type="number" id="sale-qty" name="quantidade" min="1" value="1">
                            </div>
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <label for="sale-value">Valor</label>
                                <input type="text" id="sale-value" value="R$ 6,00" readonly>
                            </div>
                            <div class="field">
                                <label for="sale-date">Data</label>
                                <input type="date" id="sale-date" name="data">
                            </div>
                        </div>
                        <button class="btn btn-orange" type="submit">Registrar</button>
                    </form>
                </div>

                <h2 class="section-title" style="margin-top:0;">Vendas recentes</h2>
                <div class="sales-list" id="sales-list">
                    <div class="sale-row">
                        <span class="sale-name">Coxinha de frango</span>
                        <span class="sale-qty">Quantidade x3</span>
                        <span class="sale-total">R$ 18,00</span>
                    </div>
                </div>
            </section>

            <!-- ===== RENDIMENTO ===== -->
            <section class="tab-panel" id="tab-rendimento">
                <div class="stat-grid">
                    <div class="stat-card stat-green">
                        <div class="stat-label">Vendas da Semana</div>
                        <div class="stat-value" id="stat-week">R$ 0,00</div>
                    </div>
                    <div class="stat-card stat-orange">
                        <div class="stat-label">Vendas do Mês</div>
                        <div class="stat-value" id="stat-month">R$ 0,00</div>
                    </div>
                    <div class="stat-card stat-blue">
                        <div class="stat-label">Itens vendidos</div>
                        <div class="stat-value" id="stat-items">0</div>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Vendas por produto</h3>
                    <!-- gráfico removido junto com o JS. Quando o back-end estiver
             pronto, o Andrei pode gerar esse gráfico com PHP (ex: GD,
             ou devolvendo os dados pra uma lib JS por fora deste projeto). -->
                    <div class="chart-wrap"
                        style="display:flex; align-items:center; justify-content:center; color:var(--ink-soft); font-weight:700;">
                        Gráfico será plugado aqui pelo back-end
                    </div>
                </div>
            </section>

            <!-- ===== PERFIL ===== -->
            <section class="tab-panel" id="tab-perfil">
                <div class="form-card" style="margin:0 0 30px; max-width:640px;">
                    <h1>Editar seus dados</h1>
                    <!-- ligue este form num controller de perfil em MVC/controller -->
                    <form id="profile-form" action="editar-perfil.php" method="post">
                        <div class="field-row">
                            <div class="field">
                                <label for="p-name">Nome</label>
                                <input type="text" id="p-name" name="nome">
                            </div>
                            <div class="field">
                                <label for="p-inst">Instituição</label>
                                <input type="text" id="p-inst" name="instituicao">
                            </div>
                        </div>
                        <div class="field">
                            <label for="p-email">E-mail</label>
                            <input type="email" id="p-email" name="email">
                        </div>
                        <div class="field">
                            <label for="p-pass">Senha</label>
                            <input type="password" id="p-pass" name="senha"
                                placeholder="Deixe em branco para manter a atual">
                        </div>
                        <button class="btn btn-gradient" type="submit">Editar</button>
                    </form>
                </div>
            </section>

        </main>

        <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
    </div>
    <script src="theme.js"></script>
    <script src="toggle.js"></script>
</body>

</html>