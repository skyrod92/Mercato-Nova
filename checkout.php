<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$stmt = $pdo->prepare("SELECT o.id, o.total, oi.quantity, oi.unit_price, p.name, p.icon
                       FROM orders o
                       JOIN order_items oi ON oi.order_id = o.id
                       JOIN products p ON p.id = oi.product_id
                       WHERE o.user_id = ? AND o.status = 'panier'");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();
$total = array_sum(array_map(fn($i) => $i['quantity'] * $i['unit_price'], $items));
include 'includes/header.php';
?>
<main class="page checkout-page">
  <h1>Validation de l'achat</h1>
  <?php if (!$items): ?>
    <p class="empty">Votre panier est vide.</p>
  <?php else: ?>
    <div class="checkout-grid">
      <section class="section-box">
        <h2>Récapitulatif</h2>
        <?php foreach ($items as $item): ?>
          <div class="history-line">
            <span><?= h($item['icon'] . ' ' . $item['name']) ?> x<?= (int)$item['quantity'] ?></span>
            <strong><?= formatPrice((float)($item['quantity'] * $item['unit_price'])) ?></strong>
          </div>
        <?php endforeach; ?>
        <h3>Total : <?= formatPrice((float)$total) ?></h3>
      </section>

      <form class="section-box" method="post" action="actions/validate_order.php">
        <h2>Livraison et paiement simulé</h2>
        <label>Nom complet</label>
        <input name="full_name" required placeholder="Jean Dupont">
        <label>Adresse postale</label>
        <textarea name="address" required placeholder="12 rue Exemple"></textarea>
        <label>Ville</label>
        <input name="city" required placeholder="Paris">
        <label>Code postal</label>
        <input name="postal_code" required placeholder="75000">
        <label>Numéro de carte bancaire simulé</label>
        <input name="card_number" required maxlength="19" placeholder="4242 4242 4242 4242">
        <button>Valider le paiement</button>
        <p class="muted">Paiement pédagogique simulé : aucune vraie transaction bancaire n'est réalisée.</p>
      </form>
    </div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
