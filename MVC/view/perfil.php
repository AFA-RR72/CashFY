<?php
require_once('../config/auth.php');
require_once('../config/init.php');
require_once('../model/user.php');
require_once('../model/products.php');
require_once('../model/sales.php');
require_once('../model/institutes.php');

check_login();

$user = get_user_by_id($_SESSION['id']);
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

function format_phone_number($phone_number)
{
    $number = preg_replace('/\D/', '', $phone_number);
    if (strlen($number) === 11) {
        return '(' . substr($number, 0, 2) . ') ' . substr($number, 2, 5) . '-' . substr($number, 7, 4);
    }
    if (strlen($number) === 10) {
        return '(' . substr($number, 0, 2) . ') ' . substr($number, 2, 4) . '-' . substr($number, 6, 4);
    }
    return $number;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu perfil — Cashfy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<!-- Eu meio que dei ctrl+c ctrl+v no dashboard, porque é a mesma merda basicamente. vou só mudar uns nomes e class -->

<body>
    <div class="page">
        <header class="site-header">
            <div class="container">

                <!-- Links -->

                <a href="../../index.php" class="brand"><span class="brand-mark"></span> CashFY</a>
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
                        <a href="../../index.php#contato">Contato</a>
                    </li>
                    <li>
                        <a href="sobre.php">Sobre nós</a>
                    </li>
                </ul>
                <div class="header-actions">
                    <a href="../controller/log-out.php" class="btn btn-gradient btn-sm">Sair</a>
                </div>
            </div>
        </header>

        <!-- radios escondidos que controlam as abas -->

        <main class="container" style="flex:1;">

            <div class="profile-card">
                <div class="profile-banner"></div>
                <div class="profile-body">

                    <!-- Foto de perfil -->

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


                    <!-- Resto do perfil -->

                    <div class="profile-meta">
                        <h1 id="pf-name"><?= htmlspecialchars($user['name']) ?></h1>
                        <p id="pf-desc"></p>
                    </div>
                    <?php if (!empty($user['phone_number'])): ?>
                        <a href="https://wa.me/<?= preg_replace('/\D/', '', $user['phone_number']); ?>"
                            class="profile-contact"
                            id="add-contact-btn"><?= format_phone_number($user['phone_number']); ?></a>
                    <?php endif; ?>
                </div>

            </div>
            <!-- Form para virar vendedor -->

            <?php if (isset($_GET['vendedor']) && $_GET['vendedor'] == true): ?>
                <div class="be-seller-form">
                    <div class="titulo-form-vendedor">Atualize seu perfil para tornar-se um vendedor <span
                            class="cashfy">CashFY</span></div>
                    <form action="../controller/seja_vendedor.php" method="post" novalidate>
                        <div class="field">
                            <label for="phone_number">Contato</label>
                            <input type="tel" name="phone_number" id="phone_number" placeholder="(00) 0 0000-0000"
                                pattern="[0-9]{10,11}" maxlength="16" required>
                        </div>
                        <div class="field">
                            <label for="description">Descrição</label>
                            <textarea name="description" id="description" class="description" placeholder="min. 20 letras."
                                required></textarea>
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
                            <div class="session-msg <?= $_SESSION['msg-form'] === 'Perfil atualizado com sucesso.' ? 'success' : ''; ?>"
                                id="msg-form">
                                <?= htmlspecialchars($_SESSION['msg-form']); ?>
                                <?php unset($_SESSION['msg-form']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="field">
                            <button class="btn btn-gradient btn-block" type="submit">Atualizar perfil</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Parte do vendedor -->

            <?php if (isset($user['role_id']) && $user['role_id'] == 2): ?>
                <?php $products = get_products(); ?>
                <div class="main-seller">
                    <div class="menu_details">
                        <div class="menu_details_options">
                            <a href="?products">Produtos</a>
                        </div>
                        <div class="menu_details_options">
                            <a href="?diary">Diário</a>
                        </div>
                        <div class="menu_details_options">
                            <a href="?income">Rendimento</a>
                        </div>
                        <div class="menu_details_options">
                            <a href="?profile">Perfil</a>
                        </div>
                    </div>



                    <?php if (isset($_GET['products']) && !isset($_GET['diary']) && !isset($_GET['income']) && !isset($_GET['profile'])): ?>

                        <!-- Produtos do vendedor -->

                        <a href="new_product.php">
                            <div class="pill-tag-perfil">Novo Produto</div>
                        </a>

                        <div class="seller-products">
                            <div class="product-grid" id="pf-products">
                                <?php foreach ($products as $product): ?>
                                    <?php if ($product['user_id'] == $user['id']): ?>
                                        <div class="product-card perfil-card">
                                            <span class="badge-new">Novo!</span>
                                            <div class="product-img">
                                                <img src="<?= "../../" . $product['product_photo'] ?>" alt="Image">
                                            </div>
                                            <p class="product-name">
                                                <?= htmlspecialchars($product['name']) ?? ''; ?>
                                            </p>
                                            <p class="product-desc">
                                                <?= htmlspecialchars($product['description']) ?? ''; ?>
                                            </p>
                                            <div class="lower-session">
                                                <p class="product-price">
                                                    <?= 'R$ ' . htmlspecialchars(number_format(($product['price'] ?? 0), 2, ',', '.')); ?>
                                                </p>
                                                <div>
                                                    <a href="update_product.php?id=<?= $product['id']; ?>">
                                                        <i class="fa-solid fa-pen-to-square update-product"></i>
                                                    </a>
                                                    <a href="../controller/delete_product.php?id=<?= $product['id']; ?>">
                                                        <i class="fa-regular fa-trash-can delete-product"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>


                    <?php elseif (!isset($_GET['products']) && isset($_GET['diary']) && !isset($_GET['income']) && !isset($_GET['profile'])): ?>

                        <!-- Diário do Vendedor -->

                        <section class="tab-panel" id="tab-diario">

                            <div class="diary">
                                <div class="daily-card">
                                    <h3>+ REGISTRAR VENDAS</h3>
                                    <form action="../controller/sales.php" method="post" novalidate>
                                        <div class="field-row">
                                            <div class="field">
                                                <label for="product">Produto</label>
                                                <select name="product" id="product">
                                                    <option value="" disabled selected>Selecione um produto:</option>
                                                    <?php foreach ($products as $product): ?>
                                                        <?php if ($product['user_id'] == $user['id']): ?>
                                                            <option value="<?= $product['id']; ?>"
                                                                data-price="<?= $product['price']; ?>">
                                                                <?= htmlspecialchars($product['name']); ?>
                                                            </option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label for="quantity">Quantidade</label>
                                                <input type="number" name="quantity" id="quantity">
                                            </div>
                                            <div class="field">
                                                <label for="price">Valor</label>
                                                <div class="price-wrapper diary">
                                                    <span>R$</span>
                                                    <input type="number" id="price" name="price" min="0" step="0.01"
                                                        placeholder="0.00" required>
                                                </div>
                                            </div>
                                            <div class="field date">
                                                <label for="date">Data</label>
                                                <input type="date" name="date" id="date">
                                            </div>
                                            <div class="field">
                                                <button class="btn btn-orange" type="submit">Registrar </button>
                                            </div>
                                        </div>
                                    </form>
                                    <?php if (isset($_SESSION['msg-form'])): ?>
                                        <div class="session-msg <?= $_SESSION['msg-form'] === 'Venda registrada' ? 'success' : ''; ?>"
                                            id="msg">
                                            <?= htmlspecialchars($_SESSION['msg-form']); ?>
                                            <?php unset($_SESSION['msg-form']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="history-sale">
                                    <h2 class="section-title" style="margin-top:0;">HISTÓRICO DE VENDAS</h2>
                                    <?php $sales = get_sales(); ?>
                                    <?php foreach ($sales as $sale): ?>
                                        <?php if ($sale['user_id'] == $user['id']): ?>
                                            <?php $product = get_product_by_id($sale['product_id']); ?>
                                            <div class="sale-row">
                                                <div class="row-duos">
                                                    <span class="sale-name">
                                                        <?= $product['name']; ?>
                                                    </span>
                                                    <span class="sale-date">
                                                        <?= date('d/m/y', strtotime($sale['date'])); ?>
                                                    </span>
                                                </div>
                                                <div class="row-duos">
                                                    <span class="sale-qty">
                                                        <?= 'Quantidade: ' . $sale['quantity']; ?>
                                                    </span>
                                                    <span class="sale-total">
                                                        <?= 'Total: R$' . number_format(($sale['quantity'] * $sale['price']), 2, '.', ','); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <br>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php elseif (!isset($_GET['products']) && !isset($_GET['diary']) && isset($_GET['income']) && !isset($_GET['profile'])): ?>

                        <!-- Rendimentos do vendedor -->

                        <section class="tab-panel" id="tab-rendimento">
                            <div class="stat-grid">
                                <div class="stat-card stat-green">
                                    <div class="stat-label">Vendas da Semana</div>
                                    <div class="stat-value" id="stat-week">R$
                                        <?= htmlspecialchars(get_total_week($user['id'])) ?>
                                    </div>
                                </div>
                                <div class="stat-card stat-orange">
                                    <div class="stat-label">Vendas do Mês</div>
                                    <div class="stat-value" id="stat-month">R$
                                        <?= htmlspecialchars(get_total_mouth($user['id'])); ?>
                                    </div>
                                </div>
                                <div class="stat-card stat-blue">
                                    <div class="stat-label">Itens vendidos</div>
                                    <div class="stat-value" id="stat-items">0</div>
                                </div>
                            </div>
                            <div class="chart-card">
                                <h3>Vendas por produto</h3>
                                <!-- gráfico removido junto com o JS. Quando o back-end estiver pronto, o Andrei pode gerar esse gráfico com PHP (ex: GD, ou devolvendo os dados pra uma lib JS por fora deste projeto).-->
                                <div class="chart-wrap"
                                    style="display:flex; align-items:center; justify-content:center; color:var(--ink-soft); font-weight:700;">
                                    Gráfico será plugado aqui pelo back-end
                                </div>
                            </div>
                        </section>

                    <?php elseif (!isset($_GET['products']) && !isset($_GET['diary']) && !isset($_GET['income']) && isset($_GET['profile'])): ?>

                        <!-- Perfil do vendedor -->

                        <?php $institutes = get_institutes(); ?>

                        <section class="tab-panel" id="tab-perfil">
                            <div class="form-card seller-profile">
                                <h1>Editar seus dados</h1>
                                <form id="profile-form" action="../controller/update_user.php" method="post" novalidate>
                                    <div class="row">
                                        <div class="field profile-name">
                                            <label for="p-name">Nome</label>
                                            <input type="text" id="p-name" name="name" placeholder="Seu nome completo">
                                        </div>
                                        <div class="field select">
                                            <label for="p-inst">Instituição</label>
                                            <select name="inst" id="p-inst">
                                                <option value="" selected disabled>Selecione uma Instituição</option>
                                                <?php foreach ($institutes as $institute): ?>
                                                    <option value="<?= $institute['id'] ?>">
                                                        <?= htmlspecialchars($institute['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field phone">
                                            <label for="phone">Telefone</label>
                                            <input type="tel" name="phone" id="phone_number" placeholder="(00) 0 0000-0000" maxlength="16" required>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="email">E-mail</label>
                                        <input type="email" id="p-email" name="email" placeholder="voce@exemplo.com"
                                            autocomplete="email" required>
                                    </div>
                                    <div class="field">
                                        <label for="p-email">Descrição</label>
                                        <textarea name="description" id="p-description" class="description"
                                            placeholder="min. 20 letras." required></textarea>
                                    </div>
                                    <div class="field">
                                        <label for="p-pass">Senha</label>
                                        <input type="password" id="p-pass" name="password" placeholder="••••••••">
                                    </div>
                                    <?php if (isset($_SESSION['msg-form'])): ?>
                                        <div class="session-msg <?= $_SESSION['msg-form'] === 'Perfil alterado com sucesso' ? 'success' : ''; ?>"
                                            id="msg-form">
                                            <?= htmlspecialchars($_SESSION['msg-form']); ?>
                                            <?php unset($_SESSION['msg-form']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <button class="btn btn-gradient" type="submit">Editar</button>
                                </form>
                            </div>
                        </section>

                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>

        <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
    </div>
    <script src="../../assets/js/PhoneFormat.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/toggle.js"></script>
    <script src="../../assets/js/menu-toggle.js"></script>
    <script>
        const productSelect = document.getElementById('product');
        const priceInput = document.getElementById('price');
        const dateInput = document.getElementById('date');
        const quantity = document.getElementById('quantity');

        productSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];

            priceInput.value = Number(selectedOption.dataset.price).toFixed(2);
        });

        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
        quantity.value = 1;
    </script>
    <script>
        const user = <?= json_encode($user); ?>

        const name = document.getElementById('p-name');
        const inst = document.getElementById('p-inst');
        const phone = document.getElementById('phone_number');
        const email = document.getElementById('p-email');
        const description = document.getElementById('p-description');

        name.value = user.name;
        inst.value = user.institute_id;
        email.value = user.email;
        description.value = user.description;

        phone.value = user.phone_number.replace(/\D/g, '');

        if (phone.value.length === 11) {
            phone.value = `(${phone.value.slice(0, 2)}) ${phone.value.slice(2, 7)}-${phone.value.slice(7)}`;
        } else if (phone.value.length === 10) {
            phone.value = `(${phone.value.slice(0, 2)}) ${phone.value.slice(2, 6)}-${phone.value.slice(6)}`;
        }

    </script>
    <script src="../../assets/js/ScrollToMsg.js"></script>
</body>

</html>