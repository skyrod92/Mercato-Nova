<?php
require_once "config/database.php";
require_once "includes/functions.php";
requireLogin();

// Récupération triée par mode de vente pour scinder l'affichage
$stmt = $pdo->prepare("SELECT p.* FROM products p 
                       JOIN favorites f ON p.id = f.product_id 
                       WHERE f.user_id = ? 
                       ORDER BY p.sale_mode ASC, f.created_at DESC");
$stmt->execute([$_SESSION["user_id"]]);
$allFavorites = $stmt->fetchAll();

$normalProducts = array_filter($allFavorites, function($p) { return $p['sale_mode'] !== 'enchere'; });
$auctionProducts = array_filter($allFavorites, function($p) { return $p['sale_mode'] === 'enchere'; });

include "includes/header.php";
?>
<main style="max-width: 1100px; margin: 40px auto; padding: 20px;">
  <h1>Mes Favoris</h1>

  <!-- SECTION 1 : PRODUITS CLASSIQUES -->
  <section style="margin-bottom: 50px;">
    <h2>🛒 Achats Immédiats & Négociations</h2>
    <?php if (empty($normalProducts)): ?>
      <p style="color: #9ea7b1;">Aucun produit classique dans vos favoris.</p>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($normalProducts as $product): ?>
          <article class="product-card">
            <a href="produit.php?id=<?= (int)$product["id"] ?>" style="text-decoration: none; color: inherit; display: block;">
              <div class="product-img"><?= h($product["icon"]) ?></div>
              <div class="product-info">
                <h3><?= h($product["name"]) ?></h3>
                <span class="price"><?= formatPrice((float)$product['price']) ?></span>
              </div>
            </a>
            <div style="padding: 0 15px 15px 15px; display: flex; gap: 5px; flex-direction: column;">
              <form method="post" action="actions/add_cart.php">
                <input type="hidden" name="product_id" value="<?= (int) $product["id"] ?>">
                <button type="submit" style="width: 100%;">Ajouter au panier</button>
              </form>
              <form method="post" action="actions/toggle_favorite.php">
                  <input type="hidden" name="product_id" value="<?= (int) $product["id"] ?>">
                  <button class="favorite-btn" type="submit" style="width: 100%;">♥ Retirer</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- SECTION 2 : PRODUITS EN ENCHÈRES -->
  <section>
    <h2>🔨 Ventes aux Enchères</h2>
    <?php if (empty($auctionProducts)): ?>
      <p style="color: #9ea7b1;">Aucune enchère en cours dans vos favoris.</p>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($auctionProducts as $product): 
            $bidInfo = $pdo->prepare("SELECT MAX(amount) as max_bid, COUNT(*) as count FROM bids WHERE product_id = ?");
            $bidInfo->execute([$product['id']]);
            $stats = $bidInfo->fetch();
            $currentDisplayPrice = $stats['max_bid'] ?? $product['price'];
        ?>
          <article class="product-card">
            <a href="produit.php?id=<?= (int)$product["id"] ?>" style="text-decoration: none; color: inherit; display: block;">
              <div class="product-img"><?= h($product["icon"]) ?></div>
              <div class="product-info">
                <h3><?= h($product["name"]) ?></h3>
                <span class="price" style="color: var(--primary);"><?= formatPrice((float)$currentDisplayPrice) ?></span>
                <p style="font-size: 0.8rem; color: #9ea7b1; margin-top: 5px;">
                    (<?= (int)$stats['count'] ?> propositions)
                </p>
              </div>
            </a>
            <div style="padding: 0 15px 15px 15px;">
              <form method="post" action="actions/toggle_favorite.php">
                  <input type="hidden" name="product_id" value="<?= (int) $product["id"] ?>">
                  <button class="favorite-btn" type="submit" style="width: 100%;">♥ Retirer</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php include "includes/footer.php"; ?>