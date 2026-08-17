<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu painel — Cashfy</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="page">

    <header class="site-header">
      <div class="container">
        <a href="../../index.php" class="brand"><span class="brand-mark"></span> Cashfy</a>
        <ul class="nav-links">
          <li><a href="../../index.php">Home</a></li>
          <li><a href="../../index.php#contato">Contato</a></li>
          <li><a href="sobre.php">Sobre nós</a></li>
        </ul>
        <div class="header-actions">
          <!-- "Sair" ainda é só um link. Quando o Andrei fizer a sessão em PHP,
             troca por uma action que dá destroy na sessão. 
             
             -Andrei: Eu sei como funciona, Carlos. ;-;

             -->
          <a href="../controller/log-out.php" class="btn btn-gradient btn-sm">Sair</a>
        </div>
      </div>
    </header>

    <!-- radios escondidos que controlam as abas, sem JS -->
    <input type="radio" name="dash-tab" id="r-produtos" class="tab-radio" checked>
    <input type="radio" name="dash-tab" id="r-diario" class="tab-radio">
    <input type="radio" name="dash-tab" id="r-rendimento" class="tab-radio">
    <input type="radio" name="dash-tab" id="r-perfil" class="tab-radio">

    <main class="container" style="flex:1;">

      <div class="profile-card">
        <div class="profile-banner"></div>
        <div class="profile-body">
          <div class="avatar" id="pf-avatar">F</div>
          <div class="profile-meta">
            <h1 id="pf-name">Fulano de Tal</h1>
            <p id="pf-desc">Descrição</p>
          </div>
          <a href="#" class="profile-contact" id="add-contact-btn">Contato</a>
        </div>
      </div>

      <div class="dash-toolbar">
        <div class="tabs">
          <label class="tab-btn" for="r-produtos">Produtos</label>
          <label class="tab-btn" for="r-diario">Diário</label>
          <label class="tab-btn" for="r-rendimento">Rendimento</label>
          <label class="tab-btn" for="r-perfil">Perfil</label>
        </div>
        <button class="btn btn-outline" id="add-method-btn" type="button">Adicionar método de contato</button>
      </div>

      <!-- ===== PRODUTOS ===== -->
      <section class="tab-panel" id="tab-produtos">
        <a class="btn btn-orange" href="produto-form.php">+ Adicionar produtos</a>
        <div class="product-grid" id="my-products" style="margin-top:20px;">

          <div class="product-card">
            <div class="product-img">Imagem</div>
            <p class="product-name">Coxinha de frango</p>
            <p class="product-desc">Feita na hora, crocante por fora</p>
            <div class="product-row-actions">
              <p class="product-price" style="margin:0;">R$ 6,00</p>
              <div class="product-actions">
                <a class="icon-btn" title="Editar" href="produto-form.php?id=1">✎</a>
                <!-- ligue esse form num controller de exclusão em MVC/controller -->
                <form method="post" action="excluir-produto.php" style="display:inline;">
                  <input type="hidden" name="id" value="1">
                  <button class="icon-btn danger" title="Excluir" type="submit">🗑</button>
                </form>
              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- ===== DIÁRIO ===== -->
      <section class="tab-panel" id="tab-diario">
        <div class="daily-card">
          <h3>+ Registrar vendas</h3>
          <!-- ligue este form num controller de vendas em MVC/controller -->
          <form id="sale-form" action="registrar-venda.php" method="post">
            <div class="field-row">
              <div class="field">
                <label for="sale-product">Produto</label>
                <select id="sale-product" name="produto_id">
                  <option value="1">Coxinha de frango</option>
                </select>
              </div>
              <div class="field">
                <label for="sale-qty">Quantidade</label>
                <input type="number" id="sale-qty" name="quantidade" min="1" value="1">
              </div>
            </div>
            <div class="field-row">
              <div class="field">
                <label for="sale-value">Valor</label>
                <input type="text" id="sale-value" value="R$ 6,00" readonly>
              </div>
              <div class="field">
                <label for="sale-date">Data</label>
                <input type="date" id="sale-date" name="data">
              </div>
            </div>
            <button class="btn btn-orange" type="submit">Registrar</button>
          </form>
        </div>

        <h2 class="section-title" style="margin-top:0;">Vendas recentes</h2>
        <div class="sales-list" id="sales-list">
          <div class="sale-row">
            <span class="sale-name">Coxinha de frango</span>
            <span class="sale-qty">Quantidade x3</span>
            <span class="sale-total">R$ 18,00</span>
          </div>
        </div>
      </section>

      <!-- ===== RENDIMENTO ===== -->
      <section class="tab-panel" id="tab-rendimento">
        <div class="stat-grid">
          <div class="stat-card stat-green">
            <div class="stat-label">Vendas da Semana</div>
            <div class="stat-value" id="stat-week">R$ 0,00</div>
          </div>
          <div class="stat-card stat-orange">
            <div class="stat-label">Vendas do Mês</div>
            <div class="stat-value" id="stat-month">R$ 0,00</div>
          </div>
          <div class="stat-card stat-blue">
            <div class="stat-label">Itens vendidos</div>
            <div class="stat-value" id="stat-items">0</div>
          </div>
        </div>
        <div class="chart-card">
          <h3>Vendas por produto</h3>
          <!-- gráfico removido junto com o JS. Quando o back-end estiver
             pronto, o Andrei pode gerar esse gráfico com PHP (ex: GD,
             ou devolvendo os dados pra uma lib JS por fora deste projeto). -->
          <div class="chart-wrap"
            style="display:flex; align-items:center; justify-content:center; color:var(--ink-soft); font-weight:700;">
            Gráfico será plugado aqui pelo back-end
          </div>
        </div>
      </section>

      <!-- ===== PERFIL ===== -->
      <section class="tab-panel" id="tab-perfil">
        <div class="form-card" style="margin:0 0 30px; max-width:640px;">
          <h1>Editar seus dados</h1>
          <!-- ligue este form num controller de perfil em MVC/controller -->
          <form id="profile-form" action="editar-perfil.php" method="post">
            <div class="field-row">
              <div class="field">
                <label for="p-name">Nome</label>
                <input type="text" id="p-name" name="nome">
              </div>
              <div class="field">
                <label for="p-inst">Instituição</label>
                <input type="text" id="p-inst" name="instituicao">
              </div>
            </div>
            <div class="field">
              <label for="p-email">E-mail</label>
              <input type="email" id="p-email" name="email">
            </div>
            <div class="field">
              <label for="p-pass">Senha</label>
              <input type="password" id="p-pass" name="senha" placeholder="Deixe em branco para manter a atual">
            </div>
            <button class="btn btn-gradient" type="submit">Editar</button>
          </form>
        </div>
      </section>

    </main>

    <footer class="site-footer">Cashfy — feito por estudantes, para estudantes.</footer>
  </div>
  <script src="theme.js"></script>
</body>

</html>