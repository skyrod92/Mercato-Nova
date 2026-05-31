<?php
require_once "config/database.php";
require_once "includes/functions.php";

$search = trim($_GET["search"] ?? "");
$category = $_GET["category"] ?? "all";
$saleMode = $_GET["sale_mode"] ?? "all";
$minPrice = trim($_GET["min_price"] ?? "");
$maxPrice = trim($_GET["max_price"] ?? "");
$sort = $_GET["sort"] ?? "recent";

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

if ($saleMode !== "all") {
  $sql .= " AND sale_mode = ?";
  $params[] = $saleMode;
}

if ($minPrice !== "" && is_numeric($minPrice)) {
  $sql .= " AND price >= ?";
  $params[] = (float)$minPrice;
}

if ($maxPrice !== "" && is_numeric($maxPrice)) {
  $sql .= " AND price <= ?";
  $params[] = (float)$maxPrice;
}

switch ($sort) {
  case "price_asc":
    $sql .= " ORDER BY price ASC";
    break;
  case "price_desc":
    $sql .= " ORDER BY price DESC";
    break;
  case "name":
    $sql .= " ORDER BY name ASC";
    break;
  default:
    $sql .= " ORDER BY created_at DESC";
}

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
    <div class="catalogue-header">
      <h2>Catalogue</h2>
      <p>Recherchez un article, filtrez par catégorie, prix ou mode de vente.</p>
    </div>

    <form class="filters filters-advanced" method="get">
      <input name="search" value="<?= h($search) ?>" type="text" placeholder="Rechercher un produit...">

      <select name="category">
        <option value="all">Toutes les catégories</option>
        <option value="tech" <?= $category === "tech" ? "selected" : "" ?>>Électronique</option>
        <option value="mode" <?= $category === "mode" ? "selected" : "" ?>>Mode / vêtements</option>
        <option value="maison" <?= $category === "maison" ? "selected" : "" ?>>Maison</option>
        <option value="livres" <?= $category === "livres" ? "selected" : "" ?>>Livres</option>
        <option value="jeux" <?= $category === "jeux" ? "selected" : "" ?>>Jeux vidéo</option>
        <option value="sport" <?= $category === "sport" ? "selected" : "" ?>>Sport</option>
      </select>

      <select name="sale_mode">
        <option value="all">Tous les modes</option>
        <option value="achat" <?= $saleMode === "achat" ? "selected" : "" ?>>Achat immédiat</option>
        <option value="enchere" <?= $saleMode === "enchere" ? "selected" : "" ?>>Enchère</option>
        <option value="negociation" <?= $saleMode === "negociation" ? "selected" : "" ?>>Négociation</option>
      </select>

      <input name="min_price" value="<?= h($minPrice) ?>" type="number" step="0.01" placeholder="Prix min">
      <input name="max_price" value="<?= h($maxPrice) ?>" type="number" step="0.01" placeholder="Prix max">

      <select name="sort">
        <option value="recent" <?= $sort === "recent" ? "selected" : "" ?>>Plus récents</option>
        <option value="price_asc" <?= $sort === "price_asc" ? "selected" : "" ?>>Prix croissant</option>
        <option value="price_desc" <?= $sort === "price_desc" ? "selected" : "" ?>>Prix décroissant</option>
        <option value="name" <?= $sort === "name" ? "selected" : "" ?>>Nom A-Z</option>
      </select>

      <button>Filtrer</button>
      <a class="btn secondary-btn reset-filter" href="index.php">Réinitialiser</a>
    </form>

    <?php if (!$products): ?>
      <p class="empty">Aucun produit ne correspond à votre recherche.</p>
    <?php endif; ?>

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
            <div class="product-img"><?= productVisual($product["icon"], "product-photo") ?></div>
            <div class="product-info">
              <span class="tag"><?= h($product["sale_mode"]) ?></span>
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
