<?php
require_once('../config/auth.php');
require_once("../model/user.php");

check_login();

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header('Location: ../../index.php');
    exit;
}

$user = get_user_by_id($_SESSION['id']);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu perfil - CashFY</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
                    Escolha uma foto para usar no seu perfil.
                </p>

                <label class="photo-upload" for="profile_photo">
                    <div class="photo-preview" id="photoPreview">
                        <?php if (empty($user['profile_photo'])): ?>
                            <span class="photo-icon">
                                <i class="fa-solid fa-user"></i>
                            </span>
                        <?php else: ?>
                            <img src="../../<?= $user['profile_photo'] ?>" alt="preview">
                        <?php endif; ?>
                    </div>
                    <div class="photo-text">
                        <strong>Escolher foto</strong>
                        <span>Clique para selecionar uma imagem</span>
                        <strong><?php if (isset($_SESSION['msg'])): ?>
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
                        </strong>
                    </div>
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*" hidden>
                </label>

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

    <script>
        const input = document.getElementById('profile_photo');
        const preview = document.getElementById('photoPreview');

        input.addEventListener('change', function () {

            const file = this.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = function (e) {
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Prévia da foto">
                `;
                preview.classList.add('has-photo');
            };

            reader.readAsDataURL(file);
        });
    </script>
    <script src="return.js"></script>
    <script src="theme.js"></script>
</body>

</html>
```