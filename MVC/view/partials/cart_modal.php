<?php
/*
 * Botão + modal do carrinho, compartilhado por index.php, categoria.php e seller.php.
 * Todos os endereços usam BASE_URL, então funcionam de qualquer página, e o campo
 * "return" leva o usuário de volta para a página em que ele estava.
 */
require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/cart_return.php');

$cart_items = $_SESSION['cart'] ?? [];
$cart_total = 0;
$cart_return = cart_current_page();
$cart_action = BASE_URL . 'MVC/controller/cart.php';
$cart_checkout = BASE_URL . 'MVC/controller/checkout.php';
?>
<!-- Botão do carrinho -->

<?php if (!empty($_SESSION['cart'])): ?>

  <button
    class="cart-button"
    onclick="cart.showModal()"
    type="button">

    <i class="fa-solid fa-cart-shopping"></i>

  </button>

<?php endif; ?>


<!-- Aviso do carrinho / checkout -->

<?php if (!empty($_SESSION['cart-msg'])): ?>
  <?php $cart_msg = $_SESSION['cart-msg']; unset($_SESSION['cart-msg']); ?>

  <style>
    .cart-toast {
      position: fixed;
      left: 50%;
      bottom: 25px;
      z-index: 2000;
      max-width: min(92vw, 420px);
      padding: 12px 18px;
      transform: translateX(-50%);
      border-radius: 12px;
      background: #FBEAE6;
      border: 1px solid #F2C7BE;
      color: #C0503D;
      font-weight: 700;
      box-shadow: 0 10px 28px rgba(0, 0, 0, .18);
    }

    .cart-toast.success {
      background: #E6F3E2;
      border-color: #CDE9C6;
      color: #2C6E45;
    }
  </style>

  <div class="cart-toast <?= !empty($cart_msg['ok']) ? 'success' : ''; ?>" id="cart-toast" role="status">
    <?= htmlspecialchars($cart_msg['text'] ?? ''); ?>
  </div>

  <script>
    setTimeout(function () {
      const toast = document.getElementById('cart-toast');
      if (toast) toast.remove();
    }, 5000);
  </script>

<?php endif; ?>


<!-- Modal do carrinho -->

<dialog id="cart">

  <div class="cart-header">

    <h3>
      <i class="fa-solid fa-cart-shopping"></i>
      Carrinho
    </h3>

    <button
      type="button"
      class="cart-close"
      aria-label="Fechar carrinho"
      onclick="cart.close()">
      <i class="fa-solid fa-xmark"></i>
    </button>

  </div>


  <div class="cart-items">

    <?php if (empty($cart_items)): ?>

      <p class="cart-empty">
        Seu carrinho está vazio.
      </p>

    <?php endif; ?>

    <?php foreach ($cart_items as $id => $item): ?>

      <?php $cart_total += $item['total']; ?>

      <div class="cart-item">

        <p class="cart-item-name">
          <?= htmlspecialchars($item['product']); ?>
        </p>

        <form
          class="cart-item-qty"
          action="<?= htmlspecialchars($cart_action); ?>"
          method="post"
          style="display:inline-flex; align-items:center; gap:10px; margin:0;">

          <input
            type="hidden"
            name="product_id"
            value="<?= (int) $id; ?>">

          <input
            type="hidden"
            name="return"
            value="<?= htmlspecialchars($cart_return); ?>">

          <?php if ((int) $item['quantity'] > 1): ?>

            <button
              type="submit"
              name="action"
              value="decrease"
              aria-label="Diminuir quantidade"
              style="background:none; border:none; color:inherit; font:inherit; cursor:pointer;">
              -
            </button>

          <?php else: ?>

            <button
              type="submit"
              name="action"
              value="remove"
              aria-label="Remover produto do carrinho"
              style="background:none; border:none; color:inherit; font:inherit; cursor:pointer;">
              <i class="fa-regular fa-trash-can"></i>
            </button>

          <?php endif; ?>

          <span><?= (int) $item['quantity']; ?></span>

          <button
            type="submit"
            name="action"
            value="increase"
            aria-label="Aumentar quantidade"
            style="background:none; border:none; color:inherit; font:inherit; cursor:pointer;">
            +
          </button>

        </form>

        <p class="cart-item-price">
          R$
          <?= number_format($item['total'], 2, ',', '.'); ?>
        </p>

      </div>

    <?php endforeach; ?>

  </div>


  <div class="cart-total">

    <span>Total</span>

    <strong>
      R$
      <?= number_format($cart_total, 2, ',', '.'); ?>
    </strong>

  </div>

  <form id="checkout-form"
    action="<?= htmlspecialchars($cart_checkout); ?>"
    method="post">
    <input type="hidden" name="return" value="<?= htmlspecialchars($cart_return); ?>">
  </form>

  <button
    type="submit"
    form="checkout-form"
    class="btn btn-gradient cart-finalize"
    <?= empty($cart_items) ? 'disabled' : ''; ?>>
    Finalizar compra
  </button>
  <button
    type="button"
    class="btn btn-transparent cart-continue"
    onclick="cart.close()">
    Continuar comprando
  </button>

</dialog>

<?php if (!empty($_SESSION['open_cart'])): ?>
  <?php unset($_SESSION['open_cart']); ?>
  <script>
    document.getElementById('cart').showModal();
  </script>
<?php endif; ?>
