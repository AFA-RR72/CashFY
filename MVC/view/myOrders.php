<?php
require_once('../config/auth.php');
require_once('../model/orders.php');

check_login();

$user = get_user_by_id((int) $_SESSION['id']);
$is_seller = ((int) ($user['role_id'] ?? 0) === 2);

// Para vendedor: permite alternar entre "Recebidos" (received) e "Feitos por mim" (mine)
$scope = 'mine';
if ($is_seller) {
    $requested_scope = (string) ($_GET['scope'] ?? 'received');
    $scope = in_array($requested_scope, ['received', 'mine'], true) ? $requested_scope : 'received';
}

function order_status_meta(string $status): array
{
    $map = [
        'pending' => ['label' => 'Aguardando vendedor', 'icon' => 'fa-hourglass-half', 'group' => 'progress', 'done' => 1],
        'accepted' => ['label' => 'Aceito', 'icon' => 'fa-circle-check', 'group' => 'progress', 'done' => 2],
        'completed' => ['label' => 'Concluído', 'icon' => 'fa-flag-checkered', 'group' => 'completed', 'done' => 3],
        'rejected' => ['label' => 'Recusado', 'icon' => 'fa-ban', 'group' => 'rejected', 'done' => 0],
    ];

    return $map[$status] ?? ['label' => ucfirst($status), 'icon' => 'fa-circle', 'group' => 'progress', 'done' => 1];
}

function render_order_card(array $order): void
{
    $status = (string) $order['status'];
    $meta = order_status_meta($status);
    $timestamp = strtotime($order['date']);
    $order_total = 0;
    $steps = [1 => 'Pedido enviado', 2 => 'Aceito pelo vendedor', 3 => 'Concluído'];
?>
    <article class="mo-card" data-order-id="<?= (int) $order['id']; ?>" data-status="<?= htmlspecialchars($status); ?>" data-group="<?= $meta['group']; ?>">
        <div class="mo-card-head">
            <div class="mo-card-title">
                <strong>Pedido #<?= (int) $order['id']; ?></strong>
                <span class="mo-date"><?= date('d/m/Y', $timestamp) . ' às ' . date('H:i', $timestamp); ?></span>
            </div>
            <span class="mo-badge is-<?= htmlspecialchars($status); ?>">
                <i class="fa-solid <?= $meta['icon']; ?>" aria-hidden="true"></i>
                <?= $meta['label']; ?>
            </span>
        </div>

        <?php if ($status !== 'rejected'): ?>
            <ol class="mo-steps" aria-label="Andamento do pedido">
                <?php foreach ($steps as $number => $label): ?>
                    <?php
                    $class = '';
                    if ($number <= $meta['done']) {
                        $class = 'is-done';
                    } elseif ($number === $meta['done'] + 1) {
                        $class = 'is-current';
                    }
                    ?>
                    <li class="mo-step <?= $class; ?>" <?= $class === 'is-current' ? 'aria-current="step"' : ''; ?>>
                        <span class="mo-step-dot"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                        <?= $label; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <div class="mo-seller">
            <div class="mo-seller-info">
                <span class="mo-avatar">
                    <?php if (!empty($order['seller_photo'])): ?>
                        <img src="../../<?= htmlspecialchars($order['seller_photo']); ?>" alt="">
                    <?php else: ?>
                        <?= htmlspecialchars(pegarIniciais($order['seller_name'])); ?>
                    <?php endif; ?>
                </span>
                <span class="mo-seller-text">
                    <small>Vendedor</small>
                    <strong><?= htmlspecialchars($order['seller_name']); ?></strong>
                </span>
            </div>
            <?php if (!empty($order['seller_phone'])): ?>
                <a class="mo-whats" href="<?= htmlspecialchars(whatsapp_url($order['seller_phone'])); ?>" target="_blank" rel="noopener noreferrer"
                    aria-label="Conversar com o vendedor no WhatsApp: <?= htmlspecialchars(format_phone_number($order['seller_phone'])); ?>">
                    <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                    <?= htmlspecialchars(format_phone_number($order['seller_phone'])); ?>
                </a>
            <?php endif; ?>
        </div>

        <ul class="mo-items">
            <?php foreach ($order['items'] as $item): ?>
                <?php
                $subtotal = $item['quantity'] * $item['price'];
                $order_total += $subtotal;
                $removed = $item['product_name'] === null;
                ?>
                <li class="mo-item">
                    <span class="mo-item-img">
                        <?php if (!empty($item['product_photo'])): ?>
                            <img src="../../<?= htmlspecialchars($item['product_photo']); ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                        <?php endif; ?>
                    </span>
                    <span class="mo-item-name">
                        <strong class="<?= $removed ? 'is-removed' : ''; ?>"><?= htmlspecialchars($item['product_name'] ?? 'Produto removido'); ?></strong>
                        <small><?= (int) $item['quantity']; ?> × R$ <?= number_format($item['price'], 2, ',', '.'); ?></small>
                    </span>
                    <span class="mo-item-subtotal">R$ <?= number_format($subtotal, 2, ',', '.'); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="mo-total">
            <span>Total</span>
            <span>R$ <?= number_format($order_total, 2, ',', '.'); ?></span>
        </div>

        <?php if ($status === 'pending'): ?>
            <p class="mo-note">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>Seu pedido foi enviado e está aguardando o vendedor aceitar. Você receberá uma notificação assim que houver resposta.</span>
            </p>
        <?php elseif ($status === 'accepted'): ?>
            <p class="mo-note">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>O vendedor aceitou seu pedido! Combine a entrega ou a retirada com ele pelo WhatsApp. O pedido só aparece como concluído quando o vendedor finalizar, e os valores podem ser ajustados por ele nesse momento.</span>
            </p>
        <?php elseif ($status === 'rejected'): ?>
            <div class="mo-note is-refused" role="note">
                <i class="fa-solid fa-comment-slash" aria-hidden="true"></i>
                <div>
                    <strong>Motivo da recusa</strong>
                    <p>
                        <?php if (trim((string) ($order['explanation'] ?? '')) !== ''): ?>
                            <?= nl2br(htmlspecialchars($order['explanation'])); ?>
                        <?php else: ?>
                            O vendedor não informou o motivo.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </article>
<?php
}

$client_orders = get_client_orders((int) $user['id']);
$seller_orders = $is_seller ? get_seller_orders((int) $user['id']) : [];
$pending_received = array_filter($seller_orders, fn($o) => $o['status'] === 'pending');
$accepted_received = array_filter($seller_orders, fn($o) => $o['status'] === 'accepted');

$totals = ['all' => count($client_orders), 'progress' => 0, 'completed' => 0, 'rejected' => 0];
foreach ($client_orders as $order) {
    $totals[order_status_meta((string) $order['status'])['group']]++;
}

$filters = [
    'all' => 'Todos',
    'progress' => 'Em andamento',
    'completed' => 'Concluídos',
    'rejected' => 'Recusados',
];

$order_ok_msgs = ['Pedido aceito', 'Pedido recusado', 'Pedido concluído'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>CashFY - Pedidos</title>
  <link rel="icon" href="../../assets/img/favicon-v2.png" type="image/png">
    <link rel="stylesheet" href="../../assets/fontawesome-free-7.3.1-web/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/orders.css">
    <link rel="stylesheet" href="../../assets/css/my-orders.css">
    <link rel="stylesheet" href="../../assets/css/ui-extras.css">
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#1F7A70">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="CashFY">
    <link rel="apple-touch-icon" href="../../assets/img/favicon-v2.png">
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
                    <li class="home client">
                        <a href="../../index.php">Home</a>
                    </li>
                    <li>
                        <a href="contact.php">Contato</a>
                    </li>
                    <li class="about">
                        <a href="sobre.php">Sobre nós</a>
                    </li>
                    <li>
                        <a href="myOrders.php" class="active" aria-current="page">Meus pedidos</a>
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

                <a href="perfil.php" class="account" aria-label="Meu perfil">
                    <div class="profile-photo-icon-mother">
                        <?php if (!empty($user['profile_photo'])): ?>
                            <span class="account-mark">
                                <img src="../../<?= htmlspecialchars($user['profile_photo']); ?>" alt="Perfil">
                            </span>
                        <?php else: ?>
                            <span class="index-account-mark-child">
                                <?= htmlspecialchars(pegarIniciais($user['name'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </header>

        <main class="container mo-page" style="flex:1;">

            <?php if ($is_seller): ?>
                <div class="mo-scope-tabs" role="tablist" aria-label="Alternar visualização de pedidos">
                    <a href="myOrders.php?scope=received"
                        class="mo-scope-tab <?= $scope === 'received' ? 'is-active' : ''; ?>"
                        <?= $scope === 'received' ? 'aria-current="page"' : ''; ?>>
                        <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                        <span>Recebidos</span>
                        <?php if (count($pending_received) > 0): ?>
                            <span class="mo-scope-badge"><?= count($pending_received); ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="myOrders.php?scope=mine"
                        class="mo-scope-tab <?= $scope === 'mine' ? 'is-active' : ''; ?>"
                        <?= $scope === 'mine' ? 'aria-current="page"' : ''; ?>>
                        <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                        <span>Feitos por mim</span>
                        <?php if ($totals['all'] > 0): ?>
                            <span class="mo-scope-count"><?= $totals['all']; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endif; ?>

            <div class="mo-hero">
                <div>
                    <?php if ($is_seller && $scope === 'received'): ?>
                        <h1>Pedidos recebidos</h1>
                        <p>Gerencie os pedidos enviados pelos clientes para a sua loja.</p>
                    <?php else: ?>
                        <h1><?= $is_seller ? 'Pedidos feitos por mim' : 'Meus pedidos'; ?></h1>
                        <p>Acompanhe em tempo real o andamento de tudo o que você pediu.</p>
                    <?php endif; ?>
                </div>
                <a class="btn btn-transparent btn-sm mo-refresh" href="myOrders.php<?= $is_seller ? '?scope=' . rawurlencode($scope) : ''; ?>">
                    <i class="fa-solid fa-rotate" aria-hidden="true"></i> Atualizar
                </a>
            </div>

            <div class="orders-notify">
                <button class="btn btn-transparent btn-sm" type="button" id="orders-notify-enable" data-sw-url="<?= BASE_URL; ?>service-worker.js" hidden>
                    Ativar notificações
                </button>
                <p class="orders-notify-status" id="orders-notify-status"></p>
            </div>

            <?php if (isset($_SESSION['msg-form'])): ?>
                <div class="session-msg <?= in_array($_SESSION['msg-form'], $order_ok_msgs, true) ? 'success' : ''; ?>"
                    id="msg">
                    <?= htmlspecialchars($_SESSION['msg-form']); ?>
                    <?php unset($_SESSION['msg-form']); ?>
                </div>
            <?php endif; ?>

            <?php if ($is_seller && $scope === 'received'): ?>

                <div class="orders-panel">
                    <div class="orders-update" id="orders-update" hidden>
                        Há novidades nos seus pedidos recebidos.
                        <a href="myOrders.php?scope=received">Atualizar</a>
                    </div>

                    <h3 class="orders-group-title">Pendentes (<?= count($pending_received); ?>)</h3>

                    <?php if (empty($pending_received)): ?>
                        <p class="orders-empty">Nenhum pedido pendente no momento.</p>
                    <?php endif; ?>

                    <?php foreach ($pending_received as $order): ?>
                        <?php $order_total = 0; ?>
                        <article class="order-card">
                            <div class="order-head">
                                <strong>Pedido #<?= (int) $order['id']; ?></strong>
                                <span class="order-date"><?= date('d/m/y H:i', strtotime($order['date'])); ?></span>
                            </div>
                            <p class="order-buyer">
                                Cliente: <?= htmlspecialchars($order['buyer_name']); ?>
                                <?php if (!empty($order['buyer_phone'])): ?>
                                    — <a href="<?= htmlspecialchars(whatsapp_url($order['buyer_phone'])); ?>" target="_blank" rel="noopener noreferrer"><?= format_phone_number($order['buyer_phone']); ?></a>
                                <?php endif; ?>
                            </p>
                            <ul class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <?php $order_total += $item['quantity'] * $item['price']; ?>
                                    <li class="order-item">
                                        <span class="order-item-name">
                                            <?= htmlspecialchars($item['product_name'] ?? 'Produto removido'); ?>
                                            <small><?= (int) $item['quantity']; ?> × R$<?= number_format($item['price'], 2, ',', '.'); ?></small>
                                        </span>
                                        <span>R$ <?= number_format($item['quantity'] * $item['price'], 2, ',', '.'); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="order-total">
                                <span>Total</span>
                                <span>R$<?= number_format($order_total, 2, ',', '.'); ?></span>
                            </div>
                            <form class="order-actions" action="../controller/order_status.php" method="post">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>">
                                <input type="hidden" name="return_to" value="myOrders.php?scope=received">
                                <button class="btn btn-orange btn-sm" type="submit" name="action" value="accept">Aceitar</button>
                                <button class="btn btn-transparent btn-sm" type="submit" name="action" value="reject"
                                    data-confirm="O cliente vai ver este motivo no pedido dele e o estoque será reposto automaticamente."
                                    data-confirm-title="Recusar pedido #<?= (int) $order['id']; ?>?"
                                    data-confirm-ok="Recusar pedido"
                                    data-confirm-icon="fa-ban"
                                    data-reason-field="explanation"
                                    data-reason-label="Motivo da recusa"
                                    data-reason-placeholder="Ex.: produto esgotado, não consigo entregar nesse horário..."
                                    data-reason-min="5"
                                    data-reason-max="255">Recusar</button>
                            </form>
                        </article>
                    <?php endforeach; ?>

                    <h3 class="orders-group-title">Aceitos (<?= count($accepted_received); ?>)</h3>

                    <?php if (empty($accepted_received)): ?>
                        <p class="orders-empty">Nenhum pedido aceito aguardando entrega.</p>
                    <?php endif; ?>

                    <?php foreach ($accepted_received as $order): ?>
                        <article class="order-card">
                            <div class="order-head">
                                <strong>Pedido #<?= (int) $order['id']; ?></strong>
                                <span class="order-date"><?= date('d/m/y H:i', strtotime($order['date'])); ?></span>
                            </div>
                            <p class="order-buyer">
                                Cliente: <?= htmlspecialchars($order['buyer_name']); ?>
                                <?php if (!empty($order['buyer_phone'])): ?>
                                    — <a href="<?= htmlspecialchars(whatsapp_url($order['buyer_phone'])); ?>" target="_blank" rel="noopener noreferrer"><?= format_phone_number($order['buyer_phone']); ?></a>
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
                                <input type="hidden" name="return_to" value="myOrders.php?scope=received">
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
                                    <span data-order-sum>R$<?= number_format($accepted_total, 2, ',', '.'); ?></span>
                                </div>
                                <p class="order-hint">Confira ou ajuste os valores antes de concluir. Pedidos concluídos saem desta lista e entram no seu rendimento.</p>
                                <div class="order-actions">
                                    <button class="btn btn-orange btn-sm" type="submit" name="action" value="complete">Concluir pedido</button>
                                </div>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>

                <div class="orders-update" id="buyer-orders-update" hidden>
                    O status de um dos seus pedidos foi atualizado.
                    <a href="myOrders.php<?= $is_seller ? '?scope=mine' : ''; ?>">Atualizar lista</a>
                </div>

                <?php if (empty($client_orders)): ?>

                    <div class="mo-empty">
                        <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                        <h2>Você ainda não fez nenhum pedido</h2>
                        <p>Quando você finalizar uma compra em qualquer loja, ela aparece aqui.</p>
                        <a class="btn btn-gradient btn-sm" href="../../index.php">Explorar produtos</a>
                    </div>

                <?php else: ?>

                    <div class="mo-summary">
                        <div class="mo-stat is-progress">
                            <span class="mo-stat-icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span>
                            <div>
                                <strong><?= $totals['progress']; ?></strong>
                                <span class="mo-stat-label">Em andamento</span>
                            </div>
                        </div>
                        <div class="mo-stat is-done">
                            <span class="mo-stat-icon"><i class="fa-solid fa-flag-checkered" aria-hidden="true"></i></span>
                            <div>
                                <strong><?= $totals['completed']; ?></strong>
                                <span class="mo-stat-label">Concluídos</span>
                            </div>
                        </div>
                        <div class="mo-stat is-refused">
                            <span class="mo-stat-icon"><i class="fa-solid fa-ban" aria-hidden="true"></i></span>
                            <div>
                                <strong><?= $totals['rejected']; ?></strong>
                                <span class="mo-stat-label">Recusados</span>
                            </div>
                        </div>
                    </div>

                    <div class="mo-filters" role="group" aria-label="Filtrar pedidos por status">
                        <?php foreach ($filters as $key => $label): ?>
                            <button type="button" class="mo-filter <?= $key === 'all' ? 'is-active' : ''; ?>"
                                data-filter="<?= $key; ?>" aria-pressed="<?= $key === 'all' ? 'true' : 'false'; ?>">
                                <?= $label; ?>
                                <span class="mo-filter-count"><?= $totals[$key]; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <section class="mo-list" id="mo-list" aria-live="polite">
                        <?php foreach ($client_orders as $order): ?>
                            <?php render_order_card($order); ?>
                        <?php endforeach; ?>

                        <div class="mo-empty" id="mo-filter-empty" hidden>
                            <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                            <h2>Nenhum pedido neste filtro</h2>
                            <p>Escolha outro filtro para ver seus pedidos.</p>
                        </div>
                    </section>

                <?php endif; ?>

            <?php endif; ?>
        </main>

        <footer class="site-footer">
            Cashfy — feito por estudantes, para estudantes &nbsp;·&nbsp;
            <span id="contato">cashfy@gmail.com</span> &nbsp;·&nbsp;
            <a href="tos.php">Termos de uso</a> &nbsp;·&nbsp;
            <a href="pp.php">Políticas de privacidade</a>
        </footer>
    </div>
    <div id="cookie-banner">
        <h3>Usamos cookies</h3>

        <p>
            O CashFY utiliza cookies para manter sua sessão, lembrar algumas de suas
            preferências e garantir o funcionamento correto do site.
        </p>

        <p>
            Os cookies ajudam, por exemplo, a manter você conectado e a preservar
            determinadas configurações enquanto navega pelo CashFY.
        </p>

        <p>
            Ao continuar navegando, você concorda com o uso desses cookies.
        </p>

        <button id="cookie-accept">Aceitar</button>
        <button id="cookie-reject">Sair</button>
    </div>

    <?php require __DIR__ . '/partials/cart_modal.php'; ?>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/cookies.js"></script>
    <script src="../../assets/js/menu-toggle.js"></script>
    <script src="../../assets/js/confirm.js"></script>
    <script src="../../assets/js/my-orders.js"></script>
    <script src="../../assets/js/ScrollToMsg.js"></script>
    <script>
        (function() {
            const brl = new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });

            document.querySelectorAll('form[data-order-edit]').forEach(function(form) {
                function update() {
                    let total = 0;

                    form.querySelectorAll('.order-item').forEach(function(item) {
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
    <?php require __DIR__ . '/partials/order_notifier.php'; ?>
</body>

</html>