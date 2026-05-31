<?php
require_once "config/database.php";
require_once "includes/functions.php";
requireLogin();

$sql = "SELECT DISTINCT p.* FROM products p 
        JOIN bids b ON p.id = b.product_id 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION["user_id"]]);
$products = $stmt->fetchAll();

include "includes/header.php";
?>
<main class="page" style="max-width: 1000px; margin: 40px auto; padding: 20px;">
  <h1>Mes enchères</h1>
  <?php if (empty($products)): ?>
    <p>Vous n'avez participé à aucune enchère pour le moment.</p>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <article class="product-card" id="bid-card-<?= (int)$product["id"] ?>">
          <a href="produit.php?id=<?= (int)$product["id"] ?>" style="text-decoration: none; color: inherit; display: block;">
            <div class="product-img"><?= h($product["icon"]) ?></div>
            <div class="product-info">
              <h3><?= h($product["name"]) ?></h3>
              <p style="margin: 10px 0; color: #9ea7b1;">
                Votre dernière mise : 
                <span style="color: var(--primary); font-weight: bold;">
                <?php 
                    $userBid = $pdo->prepare("SELECT amount FROM bids WHERE product_id = ? AND user_id = ? ORDER BY amount DESC LIMIT 1");
                    $userBid->execute([$product['id'], $_SESSION['user_id']]);
                    echo formatPrice((float)$userBid->fetchColumn());
                ?>
                </span>
              </p>
            </div>
          </a>
          <div style="padding: 0 15px 15px 15px;">
              <button onclick="retirerEnchere(<?= (int)$product['id'] ?>)" class="secondary-btn" style="width: 100%; background: #e74c3c; color: white; border: none;">
                  Retirer mon enchère
              </button>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php include "includes/footer.php"; ?>
