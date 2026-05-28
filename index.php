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
<section class="hero">
  <div>
    <p class="badge">Marketplace moderne</p>
    <h1>Achetez, vendez, négociez.</h1>
    <p>Une plateforme e-commerce avec catalogue, panier séparé, favoris, connexion et vérification email.</p>
    <div class="hero-actions">
      <a href="#catalogue" class="btn primary">Voir les produits</a>
      <?php if (!isLoggedIn()): ?>
    <a href="register.php" class="btn secondary">Créer un compte</a>
<?php endif; ?>
    </div>
  </div>
  <div class="hero-card">
    <h3>Offre du jour</h3>
    <p>Casque Bluetooth Pro</p>
    <strong>79,99 €</strong>
    <form method="post" action="actions/add_cart.php">
      <input type="hidden" name="product_id" value="2">
      <button>Ajouter au panier</button>
    </form>
  </div>
</section>

<main>
  <form class="filters" method="get">
    <input name="search" value="<?= h($search) ?>" type="text" placeholder="Rechercher un produit...">
    <select name="category">
      <option value="all">Toutes les catégories</option>
      <option value="tech" <?= $category === "tech" ? "selected" : "" ?>>Électronique</option>
      <option value="mode" <?= $category === "mode" ? "selected" : "" ?>>Mode</option>
      <option value="maison" <?= $category === "maison" ? "selected" : "" ?>>Maison</option>
      <option value="livres" <?= $category === "livres" ? "selected" : "" ?>>Livres</option>
    </select>
    <button>Filtrer</button>
  </form>

  <section id="catalogue">
    <h2>Catalogue</h2>
    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <article class="product-card">
          <div class="product-img"><?= h($product["icon"]) ?></div>
          <div class="product-info">
            <span class="tag"><?= h($product["sale_mode"]) ?></span>
            <h3><?= h($product["name"]) ?></h3>
            <p><?= h($product["description"]) ?></p>

            <?php if ($product["sale_mode"] === "enchere"): ?>
             <div class="auction-box">
              <div class="auction-time">
                <span>Expire dans</span>
                <span class="timer"
                    data-end="<?= h($product['end_date'] ?? '') ?>"
                </span>
              </div>

              <div class="time-bar">
                 <div class="time-bar-fill"
                    data-start="<?= h($product['start_date'] ?? '') ?>"
                    data-end="<?= h($product['end_date'] ?? '') ?>"
                 </div>
               </div>
              </div>
         <?php endif; ?>
            <div class="price-row">
              <span class="price"><?= formatPrice((float) $product["price"]) ?></span>
              <form method="post" action="actions/add_cart.php">
                <input type="hidden" name="product_id" value="<?= (int) $product["id"] ?>">
                <button>Ajouter</button>
              </form>
            </div>
            <form method="post" action="actions/toggle_favorite.php">
              <input type="hidden" name="product_id" value="<?= (int) $product["id"] ?>">
              <button class="favorite-btn" type="submit">
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
