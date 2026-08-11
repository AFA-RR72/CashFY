<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Produto — Cashfy</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">
  <div class="form-page-wrap">
    <div class="form-page-head">
      <a class="back-link" href="dashboard.php" style="padding:0;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Voltar
      </a>
    </div>

    <div class="form-card">
      <h1 id="form-title">+ Adicionar produto</h1>

      <!-- ligue este form num controller de produtos em MVC/controller.
           Repare em enctype="multipart/form-data", necessário pra receber
           o arquivo de imagem via $_FILES no PHP. -->
      <form id="product-form" action="salvar-produto.php" method="post" enctype="multipart/form-data">
        <label class="field-label" style="font-weight:800; color:var(--teal-900); font-size:0.92rem; display:block; margin-bottom:7px;">Imagem do produto</label>
        <label class="img-drop" for="img-input" style="cursor:pointer;">
          <span class="drop-label">+</span>
        </label>
        <input type="file" id="img-input" name="imagem" accept="image/*" style="display:none;">

        <div class="field">
          <label for="p-name">Nome do produto<span class="required">*</span></label>
          <input type="text" id="p-name" name="nome" placeholder="Ex: Coxinha de frango" required>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="p-price">Preço<span class="required">*</span></label>
            <input type="number" id="p-price" name="preco" min="0" step="0.01" placeholder="0,00" required>
          </div>
          <div class="field">
            <label for="p-category">Categoria</label>
            <select id="p-category" name="categoria">
              <option value="comida">Comida</option>
              <option value="artesanato">Artesanato</option>
              <option value="doces">Doces &amp; Sobremesas</option>
              <option value="plantas">Plantas &amp; Mudas</option>
              <option value="bebidas">Bebidas</option>
              <option value="papelaria">Papelaria</option>
              <option value="acessorios">Acessórios</option>
              <option value="roupas">Roupas</option>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="p-desc">Descrição</label>
          <textarea id="p-desc" name="descricao" placeholder="Fale um pouco sobre o produto..."></textarea>
        </div>

        <div class="form-actions">
          <a href="dashboard.php" class="btn btn-ghost" id="cancel-btn">Cancelar</a>
          <button type="submit" class="btn btn-orange" id="save-btn">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
