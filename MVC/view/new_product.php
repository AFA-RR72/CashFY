<?php session_start();
require_once("../model/category.php");

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
    <div class="auth-page">
        <a href="perfil.php" class="auth-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                stroke-linecap="round">
                <line x1="19" y1="12" x2="5" y2="12" />
                <polyline points="12 19 5 12 12 5" />
            </svg>
            Voltar
        </a>
        <div class="auth-card">
            <p class="brand"><span class="brand-mark"></span> Cashfy</p>
            <form method="post" action="../controller/new_product.php" enctype="multipart/form-data" novalidate>
                <div class="product-photo">
                    <label class="photo_upload" id="photoPreview" for="product_photo">

                        <div class="photo-preview">
                            <span class="photo-icon">
                                <i class="fa-solid fa-plus"></i>
                            </span>
                        </div>

                        <div class="photo-text">
                            <strong>Escolher foto</strong>
                            <span>Clique para selecionar uma imagem</span>
                        </div>

                        <input type="file" name="product_photo" accept="image/*" id="product_photo" hidden>

                    </label>
                </div>
                <div class="field">
                    <label for="product_name">Nome do Produto</label>
                    <input type="text" name="product_name" placeholder="Ex.: Salgado de carne" required>
                </div>
                <div class="field">
                    <label for="price">Preço</label>
                    <input type="number" name="product_price" placeholder="R$ 00.00" required>
                </div>
                <div class="field">
                    <label for="category">Categoria</label>
                    <select name="product_category">
                        <option value="" disabled selected>Selecione uma categoria</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id']; ?>"><?= $category['name']; ?></option>
                        </select>
                    <?php endforeach; ?>
                </div>
                <div class="field">
                    <label for="product_description">Descrição</label>
                    <textarea name="product_description" id="description" class="description"
                        placeholder="min. 10 letras." required></textarea>
                </div>
                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="session-msg <?= $_SESSION['msg'] === 'Produto criado com sucesso' ? 'success' : '' ?>">
                        <?= htmlspecialchars($_SESSION['msg']) ?>
                    </div>
                    <?php if ($_SESSION['msg'] === 'Produto criado com sucesso') {
                        unset($_SESSION['msg']);
                        header("Refresh: 2; url=perfil.php");
                    }
                    ?>
                    <?php unset($_SESSION['msg']); endif; ?>
                <button class="btn btn-gradient btn-block" type="submit">Criar produto</button>
            </form>
        </div>
        <script>
            const input = document.getElementById('product_photo');
            const photoPreview = document.querySelector('.photo-preview');
            const photoIcon = document.querySelector('.photo-icon');

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

                    photoIcon.style.display = 'none';
                    photoPreview.classList.add('has-photo');
                };

                reader.readAsDataURL(file);
            });
        </script>
        <script src="theme.js"></script>
</body>

</html>