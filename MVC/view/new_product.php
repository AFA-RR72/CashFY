<?php
require_once('../config/auth.php');
require_once("../model/category.php");

check_login();
check_role();

$categories = get_categories();

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Produto - Cashfy</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="page">

        <div class="form-page-wrap">

            <div class="form-page-head">

                <a href="#" onclick="voltarPagina(event)" class="back-link" style="padding:0;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                        stroke-linecap="round">
                        <line x1="19" y1="12" x2="5" y2="12" />
                        <polyline points="12 19 5 12 12 5" />
                    </svg>
                    Voltar
                </a>

            </div>

            <div class="form-card">

                <h1 id="form-title">+ Adicionar produto</h1>

                <form method="post" action="../controller/new_product.php" enctype="multipart/form-data" novalidate>

                    <!-- Imagem -->

                    <label class="field-label"
                        style="font-weight:800; color:var(--teal-900); font-size:0.92rem; display:block; margin-bottom:7px;">
                        Imagem do produto<span class="required">*</span>
                    </label>

                    <label class="img-drop" for="product_photo" id="photoPreview" style="cursor:pointer;">

                        <span class="drop-label">
                            <i class="fa-solid fa-plus"></i>
                        </span>

                        <input type="file" name="product_photo" accept="image/*" id="product_photo"
                            style="display:none;">

                    </label>


                    <!-- Nome -->

                    <div class="field">

                        <label for="product_name">
                            Nome do produto<span class="required">*</span>
                        </label>

                        <input type="text" id="product_name" name="product_name" placeholder="Ex.: Salgado de carne"
                            required>

                    </div>


                    <!-- Preço + Categoria -->

                    <div class="field-row">

                        <div class="field">

                            <label for="price">
                                Preço<span class="required">*</span>
                            </label>

                            <input type="number" id="price" name="product_price" min="0" step="0.01" placeholder="0,00"
                                required>

                        </div>


                        <div class="field">

                            <label for="category">
                                Categoria<span class="required">*</span>
                            </label>

                            <select id="category" name="product_category" required>

                                <option value="" disabled selected>
                                    Selecione uma categoria
                                </option>

                                <?php foreach ($categories as $category): ?>

                                    <option value="<?= $category['id']; ?>">
                                        <?= $category['name']; ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    </div>


                    <!-- Descrição -->

                    <div class="field">

                        <label for="description">
                            Descrição<span class="required">*</span>
                        </label>

                        <textarea id="description" name="product_desc" class="product-description"
                            placeholder="Fale um pouco sobre o produto..." required></textarea>

                    </div>


                    <!-- Mensagem -->

                    <?php if (isset($_SESSION['msg'])): ?>

                        <div class="session-msg <?= $_SESSION['msg'] === 'Produto criado com sucesso' ? 'success' : '' ?>">
                            <?= htmlspecialchars($_SESSION['msg']) ?>
                        </div>

                        <?php if ($_SESSION['msg'] === 'Produto criado com sucesso') {

                            unset($_SESSION['msg']);

                            header("Refresh: 1; url=perfil.php");
                        }

                        ?>

                        <?php unset($_SESSION['msg']); ?>

                    <?php endif; ?>


                    <!-- Botões -->

                    <div class="form-actions">

                        <a href="perfil.php" class="btn btn-ghost" id="cancel-btn">
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-orange" id="save-btn">
                            Salvar
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <!-- Preview da imagem -->

    <script>
        const input = document.getElementById('product_photo');
        const photoPreview = document.getElementById('photoPreview');
        const dropLabel = photoPreview.querySelector('.drop-label');

        input.addEventListener('change', function () {

            const file = this.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = function (e) {

                let img = photoPreview.querySelector('img');

                if (!img) {

                    img = document.createElement('img');

                    photoPreview.appendChild(img);

                }

                img.src = e.target.result;

                dropLabel.style.display = 'none';

                photoPreview.classList.add('has-photo');
            };

            reader.readAsDataURL(file);
        });
    </script>
    <script src="return.js"></script>
    <script src="theme.js"></script>
</body>

</html>
```