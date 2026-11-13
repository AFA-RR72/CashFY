<?php
require_once('../config/auth.php');
require_once('../model/sales.php');
require_once('../model/institutes.php');

check_login();

$user = get_user_by_id($_SESSION['id']);
$products = get_products();
$show_revenue_chart = false;

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

if (empty($_GET)) {
    $_GET['profile'] = true;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Meu perfil — Cashfy</title>
    <link rel="stylesheet" href="../../assets/fontawesome-free-7.3.1-web/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/orders.css">
    <link rel="stylesheet" href="../../assets/css/ui-extras.css">
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

                    <li class="home <?php if (isset($user) && $user['role_id'] == 3) {
                                        echo 'client';
                                    } ?>">
                        <a href="../../index.php">Home</a>
                    </li>

                    <li>
                        <a href="../../index.php#contato">Contato</a>
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
                <div class="header-actions">
                    <a href="../controller/log-out.php" class="btn btn-gradient btn-sm"
                        data-confirm="Você precisará entrar novamente para acessar sua conta neste dispositivo."
                        data-confirm-title="Sair da conta?"
                        data-confirm-ok="Sair"
                        data-confirm-icon="fa-right-from-bracket"
                        data-confirm-tone="neutral">Sair</a>
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
                                <?= pegarIniciais($user['name']) ?></a>
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

            <!-- Parte do vendedor -->

            <?php if (isset($user['role_id']) && $user['role_id'] == 2): ?>
                <?php
                $products = get_products();
                $order_sig = get_order_signature((int) $user['id']);
                ?>
                <div class="main-seller">
                    <div class="menu_details">
                        <div class="menu_details_options">
                            <a href="?products">Produtos</a>
                        </div>
                        <div class="menu_details_options">
                            <a href="?diary">Diário</a>
                        </div>
                        <div class="menu_details_options">
                            <a href="?orders" class="menu-link-badge"><span class="menu-link-label">Pedidos</span><span id="orders-badge" data-sw-url="<?= BASE_URL; ?>service-worker.js" class="orders-badge"
                                    data-endpoint="../controller/order_stream.php"
                                    data-orders-url="perfil.php?orders"
                                    data-pending="<?= $order_sig['pending']; ?>"
                                    data-last-id="<?= $order_sig['last_id']; ?>"
                                    <?= $order_sig['pending'] > 0 ? '' : 'hidden'; ?>><?= $order_sig['pending'] > 0 ? $order_sig['pending'] : ''; ?></span></a>
                        </div>
                        <div class="menu_details_options">
                            <a href="?income">Rendimento</a>
                        </div>
                        <div class="menu_details_options">
                            <a href="?profile">Perfil</a>
                        </div>
                    </div>



                    <?php if (isset($_GET['orders'])): ?>

                        <?php
                        $orders = get_seller_orders((int) $user['id']);
                        $pending_orders = array_filter($orders, fn($o) => $o['status'] === 'pending');
                        $accepted_orders = array_filter($orders, fn($o) => $o['status'] === 'accepted');
                        $completed_orders = array_filter($orders, fn($o) => $o['status'] === 'completed');
                        $order_ok_msgs = ['Pedido aceito', 'Pedido recusado', 'Pedido concluído'];
                        ?>

                        <section class="tab-panel" id="tab-pedidos">
                            <div class="orders-panel">
                                <h2 class="section-title" style="margin-top:0;">PEDIDOS RECEBIDOS</h2>

                                <div class="orders-notify">
                                    <button class="btn btn-transparent btn-sm" type="button" id="orders-notify-enable" data-sw-url="<?= BASE_URL; ?>service-worker.js" hidden>
                                        Ativar notificações
                                    </button>
                                    <p class="orders-notify-status" id="orders-notify-status"></p>
                                </div>

                                <div class="orders-update" id="orders-update" hidden>
                                    Há novidades nos seus pedidos.
                                    <a href="?orders">Atualizar</a>
                                </div>

                                <?php if (isset($_SESSION['msg-form'])): ?>
                                    <div class="session-msg <?= in_array($_SESSION['msg-form'], $order_ok_msgs, true) ? 'success' : ''; ?>"
                                        id="msg">
                                        <?= htmlspecialchars($_SESSION['msg-form']); ?>
                                        <?php unset($_SESSION['msg-form']); ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Pendentes: o vendedor aceita ou recusa -->

                                <h3 class="orders-group-title">Pendentes (<?= count($pending_orders); ?>)</h3>

                                <?php if (empty($pending_orders)): ?>
                                    <p class="orders-empty">Nenhum pedido pendente.</p>
                                <?php endif; ?>

                                <?php foreach ($pending_orders as $order): ?>
                                    <?php $order_total = 0; ?>
                                    <article class="order-card">
                                        <div class="order-head">
                                            <strong>Pedido #<?= (int) $order['id']; ?></strong>
                                            <span class="order-date"><?= date('d/m/y H:i', strtotime($order['date'])); ?></span>
                                        </div>
                                        <p class="order-buyer">
                                            Cliente: <?= htmlspecialchars($order['buyer_name']); ?>
                                            <?php if (!empty($order['buyer_phone'])): ?>
                                                — <a href="https://wa.me/<?= preg_replace('/\D/', '', $order['buyer_phone']); ?>"><?= format_phone_number($order['buyer_phone']); ?></a>
                                            <?php endif; ?>
                                        </p>
                                        <ul class="order-items">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <?php $order_total += $item['quantity'] * $item['price']; ?>
                                                <li class="order-item">
                                                    <span class="order-item-name">
                                                        <?= htmlspecialchars($item['product_name'] ?? 'Produto removido'); ?>
                                                        <small><?= (int) $item['quantity']; ?> × R$ <?= number_format($item['price'], 2, ',', '.'); ?></small>
                                                    </span>
                                                    <span>R$ <?= number_format($item['quantity'] * $item['price'], 2, ',', '.'); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <div class="order-total">
                                            <span>Total</span>
                                            <span>R$ <?= number_format($order_total, 2, ',', '.'); ?></span>
                                        </div>
                                        <form class="order-actions" action="../controller/order_status.php" method="post">
                                            <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>">
                                            <button class="btn btn-orange btn-sm" type="submit" name="action" value="accept">Aceitar</button>
                                            <button class="btn btn-transparent btn-sm" type="submit" name="action" value="reject"
                                                data-confirm="O cliente vai ver este motivo no pedido dele."
                                                data-confirm-title="Recusar pedido #<?= (int) $order['id']; ?>?"
                                                data-confirm-ok="Recusar pedido"
                                                data-confirm-icon="fa-ban"
                                                data-reason-field="explanation"
                                                data-reason-label="Motivo da recusa"
                                                data-reason-placeholder="Ex.: produto esgotado, não consigo entregar nesse dia..."
                                                data-reason-min="5"
                                                data-reason-max="255">Recusar</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>

                                <!-- Aceitos: ficam aqui até o vendedor concluir (pode ajustar valores antes) -->

                                <h3 class="orders-group-title">Aceitos (<?= count($accepted_orders); ?>)</h3>

                                <?php if (empty($accepted_orders)): ?>
                                    <p class="orders-empty">Nenhum pedido aceito aguardando entrega.</p>
                                <?php endif; ?>

                                <?php foreach ($accepted_orders as $order): ?>
                                    <article class="order-card">
                                        <div class="order-head">
                                            <strong>Pedido #<?= (int) $order['id']; ?></strong>
                                            <span class="order-date"><?= date('d/m/y H:i', strtotime($order['date'])); ?></span>
                                        </div>
                                        <p class="order-buyer">
                                            Cliente: <?= htmlspecialchars($order['buyer_name']); ?>
                                            <?php if (!empty($order['buyer_phone'])): ?>
                                                — <a href="https://wa.me/<?= preg_replace('/\D/', '', $order['buyer_phone']); ?>"><?= format_phone_number($order['buyer_phone']); ?></a>
                                            <?php endif; ?>
                                        </p>
                                        <?php
                                        $accepted_total = 0;
                                        foreach ($order['items'] as $item) {
                                            $accepted_total += $item['quantity'] * $item['price'];
                                        }
                                        ?>
                                        <form action="../controller/order_status.php" method="post" data-order-edit>
                                            <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>">
                                            <ul class="order-items">
                                                <?php foreach ($order['items'] as $item): ?>
                                                    <li class="order-item">
                                                        <span class="order-item-name"><?= htmlspecialchars($item['product_name'] ?? 'Produto removido'); ?></span>
                                                        <span class="order-item-fields">
                                                            <input type="number" min="1" step="1"
                                                                name="items[<?= (int) $item['id']; ?>][quantity]"
                                                                value="<?= (int) $item['quantity']; ?>"
                                                                aria-label="Quantidade">
                                                            <span>× R$</span>
                                                            <input type="number" min="0.01" step="0.01"
                                                                name="items[<?= (int) $item['id']; ?>][price]"
                                                                value="<?= number_format($item['price'], 2, '.', ''); ?>"
                                                                aria-label="Valor unitário">
                                                            <span class="order-item-sum" data-item-sum>— R$ <?= number_format($item['quantity'] * $item['price'], 2, ',', '.'); ?></span>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <div class="order-total">
                                                <span>Total</span>
                                                <span data-order-sum>R$ <?= number_format($accepted_total, 2, ',', '.'); ?></span>
                                            </div>
                                            <p class="order-hint">Confira ou ajuste os valores antes de concluir. Pedidos concluídos saem desta lista e entram no seu rendimento.</p>
                                            <div class="order-actions">
                                                <button class="btn btn-orange btn-sm" type="submit" name="action" value="complete">Concluir pedido</button>
                                            </div>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>

                    <?php elseif (isset($_GET['products']) && !isset($_GET['diary']) && !isset($_GET['income']) && !isset($_GET['profile'])): ?>

                        <!-- Produtos do vendedor -->

                        <?php if (isset($_SESSION['msg-form'])): ?>
                            <div class="session-msg <?= $_SESSION['msg-form'] === 'Produto apagado' ? 'success' : ''; ?>"
                                id="msg">
                                <?= htmlspecialchars($_SESSION['msg-form']); ?>
                                <?php unset($_SESSION['msg-form']); ?>
                            </div>
                        <?php endif; ?>

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
                                                    <form class="inline-form" action="../controller/delete_product.php" method="post">
                                                        <input type="hidden" name="id" value="<?= (int) $product['id']; ?>">
                                                        <button class="icon-action" type="submit" aria-label="Apagar produto"
                                                            data-confirm="&quot;<?= htmlspecialchars($product['name'], ENT_QUOTES); ?>&quot; será removido da sua lista de produtos. Essa ação não pode ser desfeita."
                                                            data-confirm-title="Apagar produto?"
                                                            data-confirm-ok="Apagar"
                                                            data-confirm-icon="fa-trash-can">
                                                            <i class="fa-regular fa-trash-can delete-product"></i>
                                                        </button>
                                                    </form>
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
                                <button onclick="diary()">
                                    <h2 class="section-title" style="margin-top:0;">REGISTRAR VENDAS <i class="fa-solid fa-chevron-down diary-btn" id="diary-btn"></i></h2>
                                </button>


                                <div class="diary-content" id="diary">
                                    <div class="diary-card">
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
                                </div>
                                <button onclick="history()">
                                    <h2 class="section-title" style="margin-top:0;">HISTÓRICO DE PEDIDOS <i class="fa-solid fa-chevron-down history-btn" id="history-btn"></i></h2>
                                </button>
                                <div class="history-content" id="history">
                                    <?php
                                    $history_orders = get_seller_history((int) $user['id']);
                                    $history_days = [];
                                    foreach ($history_orders as $history_order) {
                                        $history_days[date('Y-m-d', strtotime($history_order['date']))][] = $history_order;
                                    }
                                    $today_key = date('Y-m-d');
                                    $yesterday_key = date('Y-m-d', strtotime('-1 day'));
                                    ?>
                                    <div class="hist">
                                        <?php if (empty($history_orders)): ?>
                                            <div class="hist-empty">
                                                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                                                <p>Nenhum pedido concluído ainda.</p>
                                            </div>
                                        <?php endif; ?>

                                        <?php foreach ($history_days as $day_key => $day_orders): ?>
                                            <?php
                                            $day_total = 0;
                                            foreach ($day_orders as $day_order) {
                                                foreach ($day_order['items'] as $day_item) {
                                                    $day_total += $day_item['quantity'] * $day_item['price'];
                                                }
                                            }
                                            $day_label = $day_key === $today_key
                                                ? 'Hoje'
                                                : ($day_key === $yesterday_key ? 'Ontem' : date('d/m/Y', strtotime($day_key)));
                                            ?>
                                            <div class="hist-day">
                                                <h3 class="hist-day-title">
                                                    <span><?= $day_label; ?></span>
                                                    <small>R$ <?= number_format($day_total, 2, ',', '.'); ?></small>
                                                </h3>

                                                <?php foreach ($day_orders as $history_order): ?>
                                                    <?php $history_total = 0; ?>
                                                    <article class="hist-card">
                                                        <div class="hist-card-head">
                                                            <div class="hist-card-title">
                                                                <strong>Pedido #<?= (int) $history_order['id']; ?></strong>
                                                                <span>
                                                                    <?= date('H:i', strtotime($history_order['date'])); ?>
                                                                    ·
                                                                    <?php if ((int) $history_order['is_manual'] === 1): ?>
                                                                        Venda avulsa (Diário)
                                                                    <?php else: ?>
                                                                        <?= htmlspecialchars($history_order['buyer_name']); ?>
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                            <span class="hist-tag"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Concluído</span>
                                                        </div>
                                                        <ul class="hist-items">
                                                            <?php foreach ($history_order['items'] as $history_item): ?>
                                                                <?php
                                                                $history_subtotal = $history_item['quantity'] * $history_item['price'];
                                                                $history_total += $history_subtotal;
                                                                ?>
                                                                <li class="hist-item">
                                                                    <span class="hist-item-name">
                                                                        <?= htmlspecialchars($history_item['product_name'] ?? 'Produto removido'); ?>
                                                                        <small><?= (int) $history_item['quantity']; ?> × R$ <?= number_format($history_item['price'], 2, ',', '.'); ?></small>
                                                                    </span>
                                                                    <span class="hist-item-total">R$ <?= number_format($history_subtotal, 2, ',', '.'); ?></span>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                        <div class="hist-card-total">
                                                            <span>Total do pedido</span>
                                                            <strong>R$ <?= number_format($history_total, 2, ',', '.'); ?></strong>
                                                        </div>
                                                    </article>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </section>
                    <?php elseif (!isset($_GET['products']) && !isset($_GET['diary']) && isset($_GET['income']) && !isset($_GET['profile'])): ?>

                        <!-- Rendimentos do vendedor -->

                        <section class="tab-panel" id="tab-rendimento">
                            <div class="stat-grid">
                                <div class="stat-card stat-green">
                                    <div class="stat-label">Vendas da Semana (atual)</div>
                                    <div class="stat-value" id="stat-week">R$
                                        <?= htmlspecialchars(get_total_week($user['id'])) ?>
                                    </div>
                                </div>
                                <div class="stat-card stat-orange">
                                    <div class="stat-label" id="stat-month-label">Vendas do Mês</div>
                                    <div class="stat-value" id="stat-month">R$
                                        <?= htmlspecialchars(get_total_month($user['id'])); ?>
                                    </div>
                                </div>
                                <div class="stat-card stat-blue">
                                    <div class="stat-label" id="stat-items-label">Itens vendidos no Mês</div>
                                    <div class="stat-value" id="stat-items">
                                        <?= htmlspecialchars(get_saled_items_month($user['id'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $show_revenue_chart = true;
                            $seller_products = get_products_by_user($user['id']);
                            $revenue_months = get_revenue_months($user['id']); // mais recente (atual) primeiro
                            $month_names = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
                            ?>
                            <div class="chart-card" id="revenue-chart-card"
                                data-endpoint="../controller/revenue_chart.php">
                                <div class="chart-header">
                                    <h3>Faturamento</h3>
                                    <div class="chart-mode" role="group" aria-label="Modo de visualização do gráfico">
                                        <button type="button" class="chart-mode-btn active" data-mode="month"
                                            aria-pressed="true">Vendas por mês</button>
                                        <button type="button" class="chart-mode-btn" data-mode="product"
                                            aria-pressed="false">Por produto</button>
                                    </div>
                                </div>

                                <div class="chart-filters">
                                    <div class="field chart-filter">
                                        <label for="chart-month">Mês</label>
                                        <select id="chart-month">
                                            <option value="all">Todos os meses</option>
                                            <?php foreach ($revenue_months as $i => $ym): ?>
                                                <option value="<?= htmlspecialchars($ym); ?>" <?= $i === 0 ? 'selected' : ''; ?>>
                                                    <?= $month_names[(int) substr($ym, 5, 2) - 1] . ' de ' . substr($ym, 0, 4); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="field chart-filter" id="chart-product-filter" hidden>
                                        <label for="chart-product">Produto</label>
                                        <select id="chart-product">
                                            <option value="" disabled selected>Selecione um produto:</option>
                                            <?php foreach ($seller_products as $seller_product): ?>
                                                <option value="<?= (int) $seller_product['id']; ?>">
                                                    <?= htmlspecialchars($seller_product['name']) . (!empty($seller_product['deleted_at']) ? ' (excluído)' : ''); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <p class="chart-summary" id="chart-summary" aria-live="polite"></p>
                                <p class="chart-message" id="chart-message" hidden></p>
                                <div class="chart-wrap" id="chart-wrap">
                                    <canvas id="revenue-chart" role="img"
                                        aria-label="Gráfico de barras do faturamento mensal"></canvas>
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
                                            <input type="tel" name="phone" id="phone_number" placeholder="(00) 0 0000-0000"
                                                maxlength="16" required>
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

            <!-- Perfil do cliente -->

            <?php if (isset($user['role_id']) && $user['role_id'] == 3): ?>
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
                            </div>
                            <div class="field">
                                <label for="email">E-mail</label>
                                <input type="email" id="p-email" name="email" placeholder="voce@exemplo.com"
                                    autocomplete="email" required>
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
        </main>

        <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
    </div>
    <script src="../../assets/js/PhoneFormat.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/toggle.js"></script>
    <script src="../../assets/js/menu-toggle.js"></script>
    <script src="../../assets/js/confirm.js"></script>
    <?php if (isset($user['role_id']) && $user['role_id'] == 2): ?>
        <script src="../../assets/js/orders-sse.js"></script>
    <?php endif; ?>
    <?php if ($show_revenue_chart): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script src="../../assets/js/revenue-chart.js"></script>
    <?php endif; ?>
    <script>
        // Pedidos aceitos: total do item (qtd × unitário) e total do pedido acompanham a edição
        (function () {
            const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

            document.querySelectorAll('form[data-order-edit]').forEach(function (form) {
                function update() {
                    let total = 0;

                    form.querySelectorAll('.order-item').forEach(function (item) {
                        const quantity = parseFloat(item.querySelector('input[name$="[quantity]"]').value) || 0;
                        const price = parseFloat(item.querySelector('input[name$="[price]"]').value) || 0;

                        total += quantity * price;
                        item.querySelector('[data-item-sum]').textContent = '— ' + brl.format(quantity * price);
                    });

                    form.querySelector('[data-order-sum]').textContent = brl.format(total);
                }

                form.addEventListener('input', update);
            });
        })();
    </script>
    <script>
        const productSelect = document.getElementById('product');
        const priceInput = document.getElementById('price');
        const dateInput = document.getElementById('date');
        const quantity = document.getElementById('quantity');

        productSelect.addEventListener('change', function() {
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
    <script>
        function diary() {
            const diary = document.getElementById('diary');
            const diaryBtn = document.getElementById('diary-btn');
            if (diary.classList.contains('active')) {
                diary.classList.remove('active');
                diaryBtn.classList.remove('active');
            } else {
                diary.classList.add('active');
                diaryBtn.classList.add('active');
            }
        }

        function history() {
            const history = document.getElementById('history');
            const historyBtn = document.getElementById('history-btn');
            if (history.classList.contains('active')) {
                history.classList.remove('active');
                historyBtn.classList.remove('active');
            } else {
                history.classList.add('active');
                historyBtn.classList.add('active');
            }
        }
    </script>
    <script src="../../assets/js/ScrollToMsg.js"></script>
</body>

</html>