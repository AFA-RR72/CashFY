<?php
session_start();
require_once("../model/institutes.php");

$institutes = get_institutes();

$oldName = $_SESSION['form']['name'] ?? '';
$oldInstitute = $_SESSION['form']['institute'] ?? '';
$oldEmail = $_SESSION['form']['email'] ?? '';
$oldTos = isset($_SESSION['form']['tos']);
$oldPp = isset($_SESSION['form']['pp']);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Criar conta — Cashfy</title>

  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

  <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

  <!-- Links -->

  <div class="auth-page">

    <a href="login.php" class="auth-back">

      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="3"
        stroke-linecap="round">

        <line x1="19" y1="12" x2="5" y2="12" />
        <polyline points="12 19 5 12 12 5" />

      </svg>

      Voltar

    </a>


    <!-- Bagulho estético -->

    <div class="auth-card">

      <p class="brand">
        <span class="brand-mark"></span> Cashfy
      </p>

      <p class="auth-sub">
        Seja bem-vindo! Crie sua conta.
      </p>


      <!-- Form -->

      <form
        id="signup-form"
        method="post"
        action="../controller/cadastro.php"
        enctype="multipart/form-data"
        novalidate
      >


        <!-- Tipo de conta -->

        <div class="field">

          <label for="account_type">
            Tipo de conta: <strong>*</strong>
          </label>

          <select name="account_type" id="account-type">

            <option value="client">
              Cliente
            </option>

            <option value="seller">
              Vendedor
            </option>

          </select>

        </div>


        <!-- Foto -->

        <div class="field">

          <p class="photo-subtitle">
            Escolha uma foto para usar no seu perfil. <br>
            (não é obrigatório)
          </p>

          <label class="photo-upload" for="profile_photo">

            <div class="photo-preview" id="photoPreview">

              <span class="photo-icon">
                <i class="fa-solid fa-user"></i>
              </span>

            </div>

            <div class="photo-text">

              <strong>Escolher foto</strong>

              <span>
                Clique para selecionar uma imagem
              </span>

            </div>

            <input
              type="file"
              name="profile_photo"
              id="profile_photo"
              accept="image/*"
              hidden
            >

          </label>

        </div>


        <!-- Nome -->

        <div class="field" id="f-name">

          <label for="name">
            Nome completo <strong>*</strong>
          </label>

          <input
            type="text"
            id="name"
            name="name"
            placeholder="Seu nome completo"
            autocomplete="name"
            required
            value="<?= htmlspecialchars($oldName) ?>"
          >

        </div>


        <!-- Instituição -->

        <div class="field">

          <label for="institute">
            Instituição de ensino <strong>*</strong>
          </label>

          <select id="institute" name="institute" required>

            <option
              value=""
              disabled
              <?= $oldInstitute === '' ? 'selected' : '' ?>
            >
              Selecione uma instituição
            </option>

            <?php foreach ($institutes as $inst): ?>

              <option
                value="<?= $inst['id'] ?>"
                <?= $oldInstitute == $inst['id'] ? 'selected' : '' ?>
              >
                <?= htmlspecialchars($inst['name']) ?>
              </option>

            <?php endforeach; ?>

          </select>

        </div>


        <!-- Email -->

        <div class="field" id="f-email">

          <label for="email">
            E-mail <strong>*</strong>
          </label>

          <input
            type="email"
            id="email"
            name="email"
            placeholder="voce@exemplo.com"
            autocomplete="email"
            required
            value="<?= htmlspecialchars($oldEmail) ?>"
          >

        </div>


        <!-- Perfil vendedor -->

        <div id="seller-profile" style="display: none;">

          <div class="field" id="f-phone">

            <label for="phone">
              Telefone <strong>*</strong>
            </label>

            <input
              type="tel"
              name="phone"
              id="phone_number"
              placeholder="(00) 0 0000-0000"
              maxlength="16"
            >

          </div>


          <div class="field" id="f-desc">

            <label for="p-description">
              Descrição <strong>*</strong>
            </label>

            <textarea
              name="description"
              id="p-description"
              class="description"
              placeholder="min. 20 letras."
            ></textarea>

          </div>

        </div>


        <!-- Senha -->

        <div class="field" id="f-pass">

          <label for="password">
            Senha <strong>*</strong>
          </label>

          <div class="password-wrapper">

            <input
              type="password"
              id="password"
              name="password"
              placeholder="••••••••"
              autocomplete="new-password"
              required
              minlength="8"
            >

            <button
              type="button"
              id="toggle_pass"
              class="password-toggle"
              onclick="toggle()"
              title="Mostrar senha"
            >

              <img
                id="eye-icon"
                src="../../uploads/icones/olhof.png"
                alt="Mostrar senha"
              >

            </button>

          </div>

        </div>


        <!-- Confirmar senha -->

        <div class="field" id="f-pass-confirm">

          <label for="pass_confirm">
            Confirme a senha <strong>*</strong>
          </label>

          <div class="password-wrapper">

            <input
              type="password"
              id="pass_confirm"
              name="pass_confirm"
              placeholder="••••••••"
              autocomplete="new-password"
              required
              minlength="8"
            >

          </div>

        </div>


        <!-- Termos -->

        <div class="field">

          <div class="checkbox-agreements">

            <input
              type="checkbox"
              name="tos"
              <?= $oldTos ? 'checked' : '' ?>
            >

            Li e concordo com os&nbsp;

            <a href="tos.php">
              Termos de Uso
            </a>

          </div>


          <div class="checkbox-agreements">

            <input
              type="checkbox"
              name="pp"
              <?= $oldPp ? 'checked' : '' ?>
            >

            Li e concordo com a&nbsp;

            <a href="pp.php">
              Política de Privacidade
            </a>

          </div>

        </div>


        <!-- Mensagem -->

        <div class="field">

          <?php if (isset($_SESSION['msg'])): ?>

            <div
              class="session-msg <?= $_SESSION['msg'] === 'Usuário criado com sucesso.' ? 'success' : '' ?>"
              id="msg"
            >

              <?= $_SESSION['msg']; ?>

            </div>

            <?php

            if ($_SESSION['msg'] === 'Usuário criado com sucesso.') {

              unset($_SESSION['msg']);
              unset($_SESSION['form']);

            ?>

              <script>

                setTimeout(() => {

                  window.location.href = "login.php";

                }, 1000);

              </script>

            <?php
            }
            ?>

          <?php endif; ?>

        </div>


        <!-- Botão -->

        <div class="field">

          <button
            class="btn btn-gradient btn-block"
            type="submit"
          >
            Criar perfil
          </button>

        </div>

      </form>


      <p class="auth-foot">

        Já tem uma conta?

        <a href="login.php">
          Entrar
        </a>

      </p>

    </div>

  </div>


  <!-- Dialog vendedor -->

  <dialog id="seller-quest">

    <h2>Atenção</h2>

    <p>
      Ao selecionar essa opção, você criará uma conta do tipo
      vendedor. Podendo oferecer produtos e necessitando que
      disponibilize seu telefone e uma breve descrição de seu perfil.
    </p>

    <button
      type="button"
      id="accept-seller"
    >
      Continuar
    </button>

    <button
      type="button"
      id="deny-seller"
    >
      Voltar
    </button>

  </dialog>


  <!-- Preview da foto -->

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


  <!-- Tipo de conta / Dialog -->

  <script>

    const account = document.getElementById('account-type');

    const sellerProfile =
      document.getElementById('seller-profile');

    const quest =
      document.getElementById('seller-quest');

    const acceptSeller =
      document.getElementById('accept-seller');

    const denySeller =
      document.getElementById('deny-seller');

    const phone =
      document.getElementById('phone_number');

    const description =
      document.getElementById('p-description');


    account.addEventListener('change', () => {

      if (account.value === 'seller') {

        quest.showModal();

      } else {

        sellerProfile.style.display = "none";

        phone.required = false;
        description.required = false;

      }

    });


    acceptSeller.addEventListener('click', () => {

      quest.close();

      sellerProfile.style.display = "block";

      phone.required = true;
      description.required = true;

    });


    denySeller.addEventListener('click', () => {

      quest.close();

      account.value = 'client';

      sellerProfile.style.display = "none";

      phone.required = false;
      description.required = false;

      phone.value = "";
      description.value = "";

    });


    /*
     * Se o usuário apertar ESC para fechar o dialog,
     * volta automaticamente para Cliente.
     */

    quest.addEventListener('close', () => {

      if (account.value === 'seller' && sellerProfile.style.display !== "block") {

        account.value = 'client';

        phone.required = false;
        description.required = false;

      }

    });

  </script>


  <script src="../../assets/js/return.js"></script>

  <script src="../../assets/js/PhoneFormat.js"></script>

  <script src="../../assets/js/toggle.js"></script>

</body>

</html>