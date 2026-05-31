<?php
require_once "config/database.php";
require_once "includes/functions.php";
$search = trim($_GET["search"] ?? "");
$category = $_GET["category"] ?? "all";
$sql = "SELECT * FROM products WHERE 1";
$params = [];
if ($search !== "") {
  $sql .= " AND (name LIKE ? OR description LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}
if ($category !== "all") {
  $sql .= " AND category = ?";
  $params[] = $category;
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$favorites = [];
if (isLoggedIn()) {
  $favStmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
  $favStmt->execute([$_SESSION["user_id"]]);
  $favorites = array_column($favStmt->fetchAll(), "product_id");
}
include "includes/header.php";
?>
<main>
  <section id="catalogue">
    <h2>Catalogue</h2>
    <div class="product-grid">
      <?php foreach ($products as $product): 
          $bidInfo = $pdo->prepare("SELECT MAX(amount) as max_bid, COUNT(*) as count FROM bids WHERE product_id = ?");
          $bidInfo->execute([$product['id']]);
          $stats = $bidInfo->fetch();
          $basePrice = (float)$product['price'];
          $maxBid = (float)($stats['max_bid'] ?? 0);
          $hasBids = $stats['count'] > 0;
          $currentDisplayPrice = $hasBids ? $maxBid : $basePrice;
          $progress = min(100, ($currentDisplayPrice / 500) * 100);
      ?>
        <article class="product-card">
          <a href="produit.php?id=<?= (int)$product["id"] ?>" style="text-decoration: none; color: inherit; display: block;">
            <div class="product-img"><?= h($product["icon"]) ?></div>
            <div class="product-info">
              <h3><?= h($product["name"]) ?></h3>
              
              <?php if ($product["sale_mode"] === "enchere"): ?>
                <div class="jauge-container" style="height: 10px; background: linear-gradient(to right, green, yellow, red); border-radius: 5px; position: relative; margin: 10px 0;">
                    <div style="position: absolute; left: <?= $progress ?>%; top: -2px; width: 3px; height: 14px; background: white;"></div>
                </div>
                <p style="font-size: 0.8rem; color: #9ea7b1; margin-bottom: 8px;">Fin : <?= h(substr($product['end_date'] ?? 'N/A', 0, 16)) ?></p>
              <?php endif; ?>

              <div class="price-row" style="display: flex; flex-direction: column; align-items: flex-start; margin-bottom: 10px;">
                <span class="price" style="font-size: 1.3rem; font-weight: bold; color: #fff;"><?= formatPrice($currentDisplayPrice) ?></span>
                
                <?php if ($product["sale_mode"] === "enchere"): ?>
                    <div style="font-size: 0.8rem; color: #9ea7b1; margin-top: 2px;">
                        <?php if ($hasBids): ?>
                            Prix de base : <span style="text-decoration: line-through;"><?= formatPrice($basePrice) ?></span> 
                            <span style="color: var(--primary);">(<?= $stats['count'] ?> propositions)</span>
                        <?php else: ?>
                            Prix de départ : <?= formatPrice($basePrice) ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
              </div>
            </div>
          </a>

          <div class="actions-block" style="padding: 0 15px 15px 15px;">
            <?php if ($product["sale_mode"] !== "enchere"): ?>
              <form method="post" action="actions/add_cart.php" style="margin-bottom: 5px;">
                <input type="hidden" name="product_id" value="<?= (int) $product["id"] ?>">
                <button type="submit" style="width: 100%;">Ajouter au panier</button>
              </form>
            <?php endif; ?>

            <form method="post" action="actions/toggle_favorite.php">
                <input type="hidden" name="product_id" value="<?= (int) $product["id"] ?>">
                <button class="favorite-btn" type="submit" style="width: 100%;">
                  <?= in_array($product["id"], $favorites) ? "♥ Retirer des favoris" : "♡ Ajouter aux favoris" ?>
                </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>
<?php include "includes/footer.php"; ?>