<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();
$stmt = $pdo->prepare("SELECT oi.product_id, oi.quantity, oi.unit_price, p.name, p.icon
                       FROM orders o
                       JOIN order_items oi ON oi.order_id = o.id
                       JOIN products p ON p.id = oi.product_id
                       WHERE o.user_id = ? AND o.status = 'panier'");$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();
$total = 0;
include 'includes/header.php';
?>
<main class="page">
  <h1>Mon panier</h1>
  <?php if (!$items): ?>
    <p class="empty">Votre panier est vide.</p>
  <?php else: ?>
    <div class="table-card">
      <?php foreach ($items as $item):
          $line = $item['quantity'] * $item['price'];
          $total += $line;
      ?>
        <div class="cart-row" id="product-row-<?= (int)$item['id'] ?>" data-price="<?= (float)$item['price'] ?>">
          <div><strong><?= h($item['icon'].' '.$item['name']) ?></strong></div>
          <span>Quantité : <input type="number" value="<?= (int)$item['quantity'] ?>" min="1" onchange="updateCart(<?= (int)$item['id'] ?>, this.value)"></span>
          <span><?= formatPrice((float)$line) ?></span>
          <button class="danger" onclick="removeItem(<?= (int)$item['id'] ?>)">Enlever</button>
        </div>
      <?php endforeach; ?>
      <div class="total">Total : <?= formatPrice((float)$total) ?></div>
    </div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
