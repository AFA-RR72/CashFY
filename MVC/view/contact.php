<?php
require_once('../config/auth.php');

if (isset($_SESSION['id'])) {
  $user = get_user_by_id($_SESSION['id']);
}

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

?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <title>Fale Conosco - CashFy</title>
</head>

<body>

    <main class="page">

        <header class="site-header">

            <div class="container">

                <a href="../../index.php" class="brand">
                    <span class="brand-mark"></span>
                    CashFY
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

                    <li class="home">
                        <a href="../../index.php">Home</a>
                    </li>

                    <li>
                        <a href="contact.php">Contato</a>
                    </li>

                    <li>
                        <a href="sobre.php">Sobre nós</a>
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

                <?php if (!isset($_SESSION['id'])): ?>

                    <!-- DESLOGADO -->

                    <div class="header-actions">

                        <a href="login.php" class="btn btn-gradient btn-sm">
                            Fazer log-in
                        </a>

                    </div>

                <?php else: ?>

                    <!-- LOGADO -->

                    <a href="perfil.php" class="account">

                        <div class="profile-photo-icon-mother">

                            <?php if (!empty($user['profile_photo'])): ?>

                                <span class="account-mark">

                                    <img
                                        src="../../<?= $user['profile_photo'] ?>"
                                        alt="Perfil">

                                </span>

                            <?php else: ?>

                                <span class="index-account-mark-child">
                                    <?= pegarIniciais($user['name']); ?>
                                </span>

                            <?php endif; ?>

                        </div>

                    </a>

                <?php endif; ?>

            </div>

        </header>

        <div class="container">

            <a
                class="back-link"
                href="#"
                onclick="voltarPagina(event)">

                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3"
                    stroke-linecap="round">

                    <line x1="19" y1="12" x2="5" y2="12" />
                    <polyline points="12 19 5 12 12 5" />

                </svg>

                Voltar

            </a>

            <div class="page-header">
                <h1>Fale Conosco</h1>
                <h3>Atendimento de segunda a sexta, 9h às 18h</h3>
                <a href="#return" id="start-page">Ir até o fim</a>
            </div>

            <hr>

            <div class="card">
                <h1>Suporte geral e dúvidas comerciais</h1>

                <ul>
                    <li>Dúvida sobre um pedido;</li>
                    <li>Parceria institucional;</li>
                    <li>Solicitação sobre dados pessoais (LGPD);</li>
                    <li>Denúncia de conteúdo ou conduta;</li>
                    <li>Outros assuntos.</li>
                </ul>

                <p>
                    Ao se comunicar via email ou mensagem, você concorda com o tratamento dos seus dados conforme
                    nossa Política de Privacidade, apenas para fins de resposta ao seu contato.
                </p>
            </div>

            <div class="card">
                <h2>@</h2>
                <h1>E-mail</h1>
                <p>administração@cashfy.com.br</p>
            </div>

            <div class="card">
                <h2>✆</h2>
                <h1>Telefone / WhatsApp</h1>
                <p>(95)99999-9999</p>
            </div>

            <div class="card">
                <h1>Perguntas frequentes</h1>

                <div>
                    <h2>Minha escola/faculdade pode participar da plataforma?</h2>

                    <p>
                        Sim. Entre em contato para conhecer o processo de adesão institucional, que inclui um acordo
                        de tratamento de dados e diretrizes de acompanhamento pedagógico.
                    </p>
                </div>

                <div>
                    <h2>Sou menor de idade, posso vender na plataforma?</h2>

                    <p>
                        Sim, desde que haja autorização da instituição de ensino e, consequentemente, autorização dos
                        responsáveis, conforme descrito em nossos Termos de Uso.
                    </p>
                </div>

                <div>
                    <h2>Como faço para excluir meus dados da plataforma?</h2>

                    <p>
                        Ao excluir sua conta, seus dados são automaticamente deletados do sistema.
                    </p>
                </div>
            </div>
            <hr>
            <div id="return">
                <a href="#" onclick="scroll()">
                    Voltar para o topo
                </a>
            </div>
        </div>
    </main>
    <script>
        window.addEventListener('load', function scroll() {
            if (window.location.hash === '#return') {
                window.history.back();
            } else {
                window.location.href = '#start-page';
            }
        });
    </script>
    <script src="../../assets/js/return.js"></script>
    <script src="../../assets/js/theme.js"></script>
</body>

</html>