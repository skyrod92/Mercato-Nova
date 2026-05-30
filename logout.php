<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();
$stmt = $pdo->prepare('SELECT p.* FROM favorites f JOIN products p ON p.id = f.product_id WHERE f.user_id = ? ORDER BY f.created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$favorites = $stmt->fetchAll();
include 'includes/header.php';
?>
<main class="page">
  <h1>Mes favoris</h1>
  <?php if (!$favorites): ?>
    <p class="empty">Aucun favori pour le moment.</p>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($favorites as $product): ?>
        <article class="product-card">
          <div class="product-img"><?= h($product['icon']) ?></div>
          <div class="product-info">
            <span class="tag"><?= h($product['sale_mode']) ?></span>
            <h3><?= h($product['name']) ?></h3>
            <p><?= h($product['description']) ?></p>
            <div class="price-row"><span class="price"><?= formatPrice((float)$product['price']) ?></span></div>
            <form method="post" action="actions/add_cart.php"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><button>Ajouter au panier</button></form>
            <form method="post" action="actions/toggle_favorite.php"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><button class="danger">Retirer des favoris</button></form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
