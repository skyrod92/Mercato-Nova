<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, u.username AS seller_name FROM products p LEFT JOIN users u ON u.id = p.seller_id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { die('Produit introuvable.'); }

$pdo->prepare("UPDATE products SET views_count = views_count + 1 WHERE id = ?")->execute([$id]);

$bidStmt = $pdo->prepare("SELECT b.*, u.username FROM bids b JOIN users u ON u.id = b.user_id WHERE b.product_id = ? ORDER BY b.amount DESC, b.created_at DESC");
$bidStmt->execute([$id]);
$bids = $bidStmt->fetchAll();
$currentBid = $bids ? max((float)$product['price'], (float)$bids[0]['amount']) : (float)$product['price'];
$minRequired = $currentBid + 1;

$reviewStmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$reviewStmt->execute([$id]);
$reviews = $reviewStmt->fetchAll();

include 'includes/header.php';
?>
<main class="product-page">
  <section class="product-detail-card">
    <div class="product-detail-image"><?= h($product['icon']) ?></div>
    <div class="product-detail-info">
      <span class="tag"><?= h($product['sale_mode']) ?></span>
      <h1><?= h($product['name']) ?></h1>
      <p><?= h($product['description']) ?></p>
      <h2><?= formatPrice((float)$currentBid) ?></h2>
      <p>État : <?= h($product['condition_product'] ?? 'occasion') ?> · Vues : <?= (int)$product['views_count'] ?></p>
      <p>Vendeur : <?= h($product['seller_name'] ?? 'Mercato Nova') ?> ⭐ 4.8/5</p>

      <div class="detail-actions">
        <?php if ($product['sale_mode'] !== 'enchere'): ?>
          <form method="post" action="actions/add_cart.php">
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
            <button>Ajouter au panier</button>
          </form>
        <?php endif; ?>
        <form method="post" action="actions/toggle_favorite.php">
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
          <button class="favorite-btn">♡ Ajouter aux favoris</button>
        </form>
      </div>

      <?php if ($product['sale_mode'] === 'enchere'): ?>
        <div class="auction-panel">
          <h3>Vente aux enchères</h3>
          <p>Prix de départ : <?= formatPrice((float)$product['price']) ?> · Mise minimale : <?= formatPrice((float)$minRequired) ?></p>
          <div id="bid-message" class="bid-message"></div>
          <form id="bid-form" onsubmit="placeBid(<?= (int)$product['id'] ?>, this); return false;">
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
            <input type="number" id="bid-amount-input" name="amount" step="0.01" min="<?= h((string)$minRequired) ?>" placeholder="Votre enchère" required>
            <button>Placer une enchère</button>
          </form>
          <button type="button" id="btn-auto-bid" class="secondary-btn">Enchère automatique</button>
          <?php if (isLoggedIn()):
            $checkUserBid = $pdo->prepare("SELECT COUNT(*) FROM bids WHERE product_id = ? AND user_id = ?");
            $checkUserBid->execute([$product['id'], $_SESSION['user_id']]);
            if ($checkUserBid->fetchColumn() > 0): ?>
              <button type="button" id="btn-retirer-mise" data-id="<?= (int)$product['id'] ?>" class="danger">Retirer ma dernière mise</button>
            <?php endif; endif; ?>
        </div>
      <?php elseif ($product['sale_mode'] === 'negociation'): ?>
        <div class="auction-panel">
          <h3>Négociation</h3>
          <p>Proposez un prix au vendeur. La négociation est simulée pour la démo.</p>
          <?php if (isLoggedIn()): ?>
            <form method="post" action="actions/send_negotiation.php">
              <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
              <input type="number" name="amount" step="0.01" placeholder="Votre proposition">
              <button>Envoyer une proposition</button>
            </form>
          <?php else: ?>
            <a class="btn" href="login.php">Connectez-vous pour négocier</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($product['sale_mode'] === 'enchere'): ?>
    <section class="section-box">
      <h2>Historique des enchères</h2>
      <?php if (!$bids): ?><p>Aucune enchère pour le moment.</p><?php endif; ?>
      <?php foreach ($bids as $bid): ?>
        <div class="history-line"><strong><?= h($bid['username']) ?></strong><span><?= formatPrice((float)$bid['amount']) ?></span></div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <section class="section-box">
    <h2>Avis produit</h2>
    <?php if (!$reviews): ?><p>Aucun avis pour le moment.</p><?php endif; ?>
    <?php foreach ($reviews as $review): ?>
      <div class="review"><strong><?= h($review['username']) ?> — <?= (int)$review['rating'] ?>/5</strong><p><?= h($review['comment']) ?></p></div>
    <?php endforeach; ?>
    <?php if (isLoggedIn()): ?>
      <form method="post" action="actions/add_review.php" class="review-form">
        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
        <input type="number" name="rating" min="1" max="5" placeholder="Note /5" required>
        <textarea name="comment" placeholder="Votre avis"></textarea>
        <button>Ajouter un avis</button>
      </form>
    <?php endif; ?>
  </section>
</main>

<div id="autoBidModal" class="modal-overlay">
  <div class="modal-card">
    <button type="button" id="btn-close-modal" class="modal-close">×</button>
    <h2>Enchère automatique</h2>
    <p>Définissez un prix maximum. Le système placera une offre automatique juste au-dessus de l'offre actuelle.</p>
    <form method="post" action="actions/auto_bid.php">
      <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
      <input type="number" name="max_amount" step="0.01" min="<?= h((string)$minRequired) ?>" placeholder="Prix cible maximum" required>
      <button>Confirmer</button>
    </form>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
