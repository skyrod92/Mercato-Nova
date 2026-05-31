<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireRole(['vendeur', 'admin']);
$userId = (int)$_SESSION['user_id'];

function handleProductImageUpload(?string $currentImage = null): string {
    $image = $currentImage ?: '📦';

    if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return $image;
    }

    if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
        return $image;
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $originalName = $_FILES['product_image']['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $_SESSION['flash'] = "Format d'image refusé. Utilisez JPG, PNG, WEBP ou GIF.";
        return $image;
    }

    $check = @getimagesize($_FILES['product_image']['tmp_name']);
    if ($check === false) {
        $_SESSION['flash'] = "Le fichier envoyé n'est pas une vraie image.";
        return $image;
    }

    $uploadDir = __DIR__ . '/uploads/products';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = 'product_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $uploadDir . '/' . $fileName;

    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $destination)) {
        return 'uploads/products/' . $fileName;
    }

    return $image;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'tech');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $saleMode = $_POST['sale_mode'] ?? 'achat';
    $condition = trim($_POST['condition_product'] ?? 'occasion');
    $start = $_POST['start_date'] ?: null;
    $end = $_POST['end_date'] ?: null;

    $currentImage = '📦';
    if ($id > 0) {
        $old = $pdo->prepare("SELECT icon FROM products WHERE id = ? AND (seller_id = ? OR ? = 'admin')");
        $old->execute([$id, $userId, currentRole()]);
        $currentImage = $old->fetchColumn() ?: '📦';
    }

    $icon = handleProductImageUpload($currentImage);

    if ($name !== '' && $description !== '' && $price > 0) {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, icon=?, description=?, price=?, sale_mode=?, condition_product=?, start_date=?, end_date=? WHERE id=? AND (seller_id=? OR ?='admin')");
            $stmt->execute([$name, $category, $icon, $description, $price, $saleMode, $condition, $start, $end, $id, $userId, currentRole()]);
            $_SESSION['flash'] = 'Produit modifié.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, icon, description, price, sale_mode, seller_id, condition_product, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $icon, $description, $price, $saleMode, $userId, $condition, $start, $end]);
            $_SESSION['flash'] = 'Produit ajouté avec son image.';
        }
        header('Location: dashboard_vendeur.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND (seller_id = ? OR ? = 'admin')");
    $stmt->execute([$id, $userId, currentRole()]);
    $_SESSION['flash'] = 'Produit supprimé.';
    header('Location: dashboard_vendeur.php');
    exit;
}

$where = currentRole() === 'admin' ? '1' : 'seller_id = ' . $userId;
$products = $pdo->query("SELECT * FROM products WHERE $where ORDER BY created_at DESC")->fetchAll();
$totalProducts = count($products);
$totalAuctions = count(array_filter($products, fn($p) => $p['sale_mode'] === 'enchere'));

$sold = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id WHERE o.status='validee' AND (p.seller_id=? OR ?='admin')");
$sold->execute([$userId, currentRole()]);
$soldCount = (int)$sold->fetchColumn();

$revenueStmt = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity*oi.unit_price),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id WHERE o.status='validee' AND (p.seller_id=? OR ?='admin')");
$revenueStmt->execute([$userId, currentRole()]);
$revenue = (float)$revenueStmt->fetchColumn();

include 'includes/header.php';
?>
<main class="page">
  <h1>Dashboard vendeur</h1>
  <div class="dashboard-grid">
    <div class="dashboard-card"><h2><?= $totalProducts ?></h2><p>Produits actifs</p></div>
    <div class="dashboard-card"><h2><?= $soldCount ?></h2><p>Produits vendus</p></div>
    <div class="dashboard-card"><h2><?= formatPrice($revenue) ?></h2><p>Revenus</p></div>
    <div class="dashboard-card"><h2><?= $totalAuctions ?></h2><p>Enchères actives</p></div>
  </div>

  <section class="section-box">
    <h2>Publier un produit</h2>
    <form class="seller-form" method="post" enctype="multipart/form-data">
      <input name="name" placeholder="Nom du produit" required>

      <select name="category">
        <option value="tech">Électronique</option>
        <option value="mode">Mode / vêtements</option>
        <option value="maison">Maison</option>
        <option value="livres">Livres</option>
        <option value="jeux">Jeux vidéo</option>
        <option value="sport">Sport</option>
      </select>

      <label class="file-upload">
        <span>Photo du produit</span>
        <input name="product_image" type="file" accept="image/png,image/jpeg,image/webp,image/gif">
      </label>

      <input name="price" type="number" step="0.01" placeholder="Prix" required>

      <select name="sale_mode">
        <option value="achat">Achat immédiat</option>
        <option value="enchere">Enchère</option>
        <option value="negociation">Négociation</option>
      </select>

      <select name="condition_product">
        <option value="neuf">Neuf</option>
        <option value="occasion" selected>Occasion</option>
        <option value="reconditionne">Reconditionné</option>
      </select>

      <input name="start_date" type="datetime-local" title="Début enchère">
      <input name="end_date" type="datetime-local" title="Fin enchère">

      <textarea name="description" placeholder="Description" required></textarea>
      <button>Ajouter le produit</button>
    </form>
    <p class="help-text">La photo est enregistrée dans le dossier <strong>uploads/products</strong> et reste visible même après déconnexion ou changement de compte.</p>
  </section>

  <section class="section-box">
    <h2>Mes produits</h2>
    <?php if (!$products): ?>
      <p class="empty">Aucun produit publié.</p>
    <?php endif; ?>
    <?php foreach ($products as $p): ?>
      <div class="history-line seller-product-line">
        <span class="mini-product">
          <?= productVisual($p['icon'], 'mini-product-img') ?>
          <span><?= h($p['name']) ?> — <?= h($p['sale_mode']) ?> — <?= formatPrice((float)$p['price']) ?></span>
        </span>
        <span>
          <a class="btn secondary-btn" href="produit.php?id=<?= (int)$p['id'] ?>">Voir</a>
          <a class="btn danger" href="dashboard_vendeur.php?delete=<?= (int)$p['id'] ?>" onclick="return confirm('Supprimer ce produit ?')">Supprimer</a>
        </span>
      </div>
    <?php endforeach; ?>
  </section>
</main>
<?php include 'includes/footer.php'; ?>
