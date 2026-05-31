<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status = 'validee' ORDER BY validated_at DESC, created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
include 'includes/header.php';
?>
<main class="page">
  <h1>Mes achats</h1>
  <?php if (!$orders): ?>
    <p class="empty">Aucun achat validé pour le moment.</p>
  <?php endif; ?>
  <?php foreach ($orders as $order): ?>
    <section class="section-box">
      <h2>Commande #<?= (int)$order['id'] ?> — <?= formatPrice((float)$order['total']) ?></h2>
      <p>Livraison : <?= h($order['full_name']) ?>, <?= h($order['address']) ?>, <?= h($order['postal_code']) ?> <?= h($order['city']) ?></p>
      <p>Carte simulée : **** <?= h($order['card_last4']) ?></p>
      <small>Validée le <?= h($order['validated_at'] ?? $order['created_at']) ?></small>
      <?php
      $items = $pdo->prepare("SELECT oi.*, p.name, p.icon FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
      $items->execute([$order['id']]);
      foreach ($items->fetchAll() as $item): ?>
        <div class="history-line"><span><?= h($item['icon'] . ' ' . $item['name']) ?> x<?= (int)$item['quantity'] ?></span><strong><?= formatPrice((float)($item['quantity'] * $item['unit_price'])) ?></strong></div>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
</main>
<?php include 'includes/footer.php'; ?>
