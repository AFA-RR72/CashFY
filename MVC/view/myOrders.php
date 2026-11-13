<?php
require_once('../config/auth.php');
require_once('../model/orders.php');

check_login();
check_role(3);

$user = get_user_by_id((int) $_SESSION['id']);

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
        return mb_strtoupper(mb_substr($palavrasValidas[0], 0, 1, 'UTF-8'), 'UTF-8');
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

function format_phone_number(string $phone_number)
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

/* Link do WhatsApp: telefones com DDD (10 ou 11 dígitos) ganham o código do Brasil (55) */
function whatsapp_url(string $phone_number): string
{
    $number = preg_replace('/\D/', '', $phone_number);

    if (strlen($number) === 10 || strlen($number) === 11) {
        $number = '55' . $number;
    }

    return 'https://wa.me/' . $number;
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
    <article class="mo-card" data-status="<?= htmlspecialchars($status); ?>" data-group="<?= $meta['group']; ?>">
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
                <span>Seu pedido foi enviado e está aguardando o vendedor aceitar. Atualize a página para ver novidades.</span>
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

$orders = get_client_orders((int) $user['id']); // do mais novo para o mais antigo

$totals = ['all' => count($orders), 'progress' => 0, 'completed' => 0, 'rejected' => 0];
foreach ($orders as $order) {
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
    <title>CashFY - Meus Pedidos</title>
    <link rel="stylesheet" href="../../assets/fontawesome-free-7.3.1-web/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/my-orders.css">
    <link rel="stylesheet" href="../../assets/css/ui-extras.css">
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

            <div class="mo-hero">
                <div>
                    <h1>Meus pedidos</h1>
                    <p>Acompanhe o andamento de tudo o que você pediu.</p>
                </div>
                <a class="btn btn-transparent btn-sm mo-refresh" href="myOrders.php">
                    <i class="fa-solid fa-rotate" aria-hidden="true"></i> Atualizar
                </a>
            </div>

            <?php if (isset($_SESSION['msg-form'])): ?>
                <div class="session-msg <?= in_array($_SESSION['msg-form'], $order_ok_msgs, true) ? 'success' : ''; ?>"
                    id="msg">
                    <?= htmlspecialchars($_SESSION['msg-form']); ?>
                    <?php unset($_SESSION['msg-form']); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>

                <div class="mo-empty">
                    <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                    <h2>Você ainda não fez nenhum pedido</h2>
                    <p>Quando você finalizar uma compra, ela aparece aqui.</p>
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
                    <?php foreach ($orders as $order): ?>
                        <?php render_order_card($order); ?>
                    <?php endforeach; ?>

                    <div class="mo-empty" id="mo-filter-empty" hidden>
                        <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                        <h2>Nenhum pedido neste filtro</h2>
                        <p>Escolha outro filtro para ver seus pedidos.</p>
                    </div>
                </section>

            <?php endif; ?>
        </main>

        <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
    </div>

    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/menu-toggle.js"></script>
    <script src="../../assets/js/my-orders.js"></script>
    <script src="../../assets/js/ScrollToMsg.js"></script>
</body>

</html>
