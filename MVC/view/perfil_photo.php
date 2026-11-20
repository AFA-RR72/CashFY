<?php
require_once('../config/auth.php');
require_once("../model/user.php");

check_login();

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header('Location: ../../index.php');
    exit;
}

$user = get_user_by_id($_SESSION['id']);

// Aviso do envio/exclusão da foto (definido em controller/perfil_photo.php)
$photo_notice = $_SESSION['photo_notice'] ?? null;
unset($_SESSION['photo_notice']);
$has_photo = !empty($user['profile_photo']);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Meu perfil - CashFY</title>
    <link rel="icon" href="../../assets/img/favicon-v2.png" type="image/png">
    <link rel="icon" href="assets/img/favicon-v2.png" type="image/png">
    <link rel="stylesheet" href="../../assets/fontawesome-free-7.3.1-web/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/photo.css">
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#1F7A70">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="CashFY">
    <link rel="apple-touch-icon" href="../../assets/img/favicon-v2.png">
</head>

<body>

    <div class="auth-page">

        <a href="#" onclick="voltarPagina(event)" class="auth-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                stroke-linecap="round">
                <line x1="19" y1="12" x2="5" y2="12" />
                <polyline points="12 19 5 12 12 5" />
            </svg>
            Voltar
        </a>

        <div class="auth-card">

            <form action="../controller/perfil_photo.php" method="POST" enctype="multipart/form-data">

                <h1 class="photo-title">Foto de perfil</h1>

                <p class="photo-subtitle">
                    <?= $has_photo ? 'Quer trocar sua foto? É só escolher outra imagem.' : 'Escolha uma foto para usar no seu perfil.' ?>
                </p>

                <?php if (!empty($photo_notice['text'])): ?>
                    <?php $notice_ok = ($photo_notice['type'] ?? '') === 'success'; ?>
                    <div class="photo-notice photo-notice--inline <?= $notice_ok ? 'is-success' : 'is-error' ?>"
                        role="status" aria-live="polite" data-photo-toast>
                        <i class="fa-solid <?= $notice_ok ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                        <span><?= htmlspecialchars($photo_notice['text']) ?></span>
                    </div>
                <?php endif; ?>

                <label class="photo-upload" for="profile_photo"
                    title="<?= $has_photo ? 'Clique para alterar a foto' : 'Clique para escolher uma foto' ?>">
                    <div class="photo-frame">
                        <div class="photo-preview" id="photoPreview">
                            <?php if (!$has_photo): ?>
                                <span class="photo-icon">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                            <?php else: ?>
                                <img src="../../<?= htmlspecialchars($user['profile_photo']) ?>" alt="Foto atual">
                            <?php endif; ?>
                        </div>
                        <span class="photo-edit-hint" aria-hidden="true">
                            <i class="fa-solid fa-camera"></i>
                            <?= $has_photo ? 'Alterar foto' : 'Escolher foto' ?>
                        </span>
                        <span class="photo-edit-badge" aria-hidden="true">
                            <i class="fa-solid fa-camera"></i>
                        </span>
                    </div>
                    <div class="photo-text">
                        <strong><?= $has_photo ? 'Alterar foto' : 'Escolher foto' ?></strong>
                        <span><?= $has_photo ? 'Clique na foto ou aqui para escolher outra imagem' : 'Clique para selecionar uma imagem' ?></span>
                    </div>
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*" hidden
                        data-photo-input data-photo-hint="#photoHint"
                        data-hint-selected="Nova foto selecionada. Clique em “Enviar foto” para salvar.">
                </label>

                <p class="photo-hint" id="photoHint" aria-live="polite">
                    <i class="fa-regular fa-hand-pointer"></i>
                    <span>Depois de escolher a imagem, clique em “Enviar foto” para salvar.</span>
                </p>

                <div class="profile-photo-form-button-mother">
                    <button class="btn btn-gradient btn-block" type="submit">
                        Enviar foto
                    </button>
                    <button class="delete_profile_photo" type="submit" name="delete_profile_photo" title="Excluir Foto">
                        <i class="fa-solid fa-trash lixeira-icone"></i>
                    </button>
                </div>

            </form>

        </div>

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

    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/photo.js"></script>
    <script>
        const input = document.getElementById('profile_photo');
        const preview = document.getElementById('photoPreview');

        input.addEventListener('change', function() {

            const file = this.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = function(e) {
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Prévia da foto">
                `;
                preview.classList.add('has-photo');
            };

            reader.readAsDataURL(file);
        });
    </script>
    <script src="../../assets/js/cookies.js"></script>
    <script src="../../assets/js/return.js"></script>
</body>

</html>