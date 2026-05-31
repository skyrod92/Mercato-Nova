<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];

if (isset($_GET['read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    $_SESSION['flash'] = 'Notifications marquées comme lues.';
    header('Location: notifications.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$sellerStmt = $pdo->prepare("
    SELECT n.*, p.name AS product_name, u.username AS buyer_name
    FROM negotiations n
    JOIN products p ON p.id = n.product_id
    JOIN users u ON u.id = n.buyer_id
    WHERE n.seller_id = ?
    ORDER BY COALESCE(n.updated_at, n.created_at) DESC
");
$sellerStmt->execute([$userId]);
$sellerNegotiations = $sellerStmt->fetchAll();

$buyerStmt = $pdo->prepare("
    SELECT n.*, p.name AS product_name, u.username AS seller_name
    FROM negotiations n
    JOIN products p ON p.id = n.product_id
    JOIN users u ON u.id = n.seller_id
    WHERE n.buyer_id = ?
    ORDER BY COALESCE(n.updated_at, n.created_at) DESC
");
$buyerStmt->execute([$userId]);
$buyerNegotiations = $buyerStmt->fetchAll();

include 'includes/header.php';
?>

<main class="page">
  <h1>Mes notifications</h1>
  <a class="btn secondary-btn" href="notifications.php?read=1">Tout marquer comme lu</a>

  <section class="section-box">
    <h2>Notifications générales</h2>

    <?php if (!$notifications): ?>
      <p>Aucune notification pour le moment.</p>
    <?php endif; ?>

    <?php foreach ($notifications as $notif): ?>
      <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>">
        <h3><?= h($notif['title']) ?></h3>
        <p><?= h($notif['content']) ?></p>
        <small><?= h($notif['created_at']) ?> <?= $notif['is_read'] ? '' : '· non lue' ?></small>
      </div>
    <?php endforeach; ?>
  </section>

  <?php if ($sellerNegotiations): ?>
    <section class="section-box">
      <h2>Négociations reçues vendeur</h2>

      <?php foreach ($sellerNegotiations as $neg): ?>
        <div class="negociation-card">
          <div>
            <h3><?= h($neg['product_name']) ?></h3>
            <p>
              Acheteur : <strong><?= h($neg['buyer_name']) ?></strong><br>
              Proposition : <strong><?= formatPrice((float)$neg['amount']) ?></strong><br>
              Statut : <strong><?= h($neg['status']) ?></strong>
            </p>

            <?php if ($neg['status'] === 'contre_offre'): ?>
              <p>Votre contre-offre : <strong><?= formatPrice((float)$neg['counter_amount']) ?></strong></p>
            <?php endif; ?>

            <?php if (!empty($neg['seller_response'])): ?>
              <p>Message vendeur : <?= h($neg['seller_response']) ?></p>
            <?php endif; ?>
          </div>

          <?php if ($neg['status'] === 'envoyee'): ?>
            <div class="negociation-actions">
              <form method="post" action="actions/respond_negotiation.php">
                <input type="hidden" name="negotiation_id" value="<?= (int)$neg['id'] ?>">
                <input type="hidden" name="action" value="accept">
                <button>Accepter</button>
              </form>

              <form method="post" action="actions/respond_negotiation.php">
                <input type="hidden" name="negotiation_id" value="<?= (int)$neg['id'] ?>">
                <input type="hidden" name="action" value="refuse">
                <button class="danger">Refuser</button>
              </form>

              <form class="counter-form" method="post" action="actions/respond_negotiation.php">
                <input type="hidden" name="negotiation_id" value="<?= (int)$neg['id'] ?>">
                <input type="hidden" name="action" value="counter">
                <input type="number" step="0.01" name="counter_amount" placeholder="Nouveau prix" required>
                <input name="seller_response" placeholder="Message optionnel">
                <button class="secondary-btn">Proposer un autre prix</button>
              </form>
            </div>
          <?php else: ?>
            <p class="muted">Cette négociation est déjà traitée.</p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <?php if ($buyerNegotiations): ?>
    <section class="section-box">
      <h2>Mes négociations acheteur</h2>

      <?php foreach ($buyerNegotiations as $neg): ?>
        <div class="negociation-card">
          <div>
            <h3><?= h($neg['product_name']) ?></h3>
            <p>
              Vendeur : <strong><?= h($neg['seller_name']) ?></strong><br>
              Votre proposition : <strong><?= formatPrice((float)$neg['amount']) ?></strong><br>
              Statut : <strong><?= h($neg['status']) ?></strong>
            </p>

            <?php if ($neg['status'] === 'contre_offre'): ?>
              <p>Contre-offre du vendeur : <strong><?= formatPrice((float)$neg['counter_amount']) ?></strong></p>
              <?php if (!empty($neg['seller_response'])): ?>
                <p>Message vendeur : <?= h($neg['seller_response']) ?></p>
              <?php endif; ?>

              <div class="negociation-actions">
                <form method="post" action="actions/respond_negotiation.php">
                  <input type="hidden" name="negotiation_id" value="<?= (int)$neg['id'] ?>">
                  <input type="hidden" name="action" value="accept_counter">
                  <button>Accepter la contre-offre</button>
                </form>

                <form method="post" action="actions/respond_negotiation.php">
                  <input type="hidden" name="negotiation_id" value="<?= (int)$neg['id'] ?>">
                  <input type="hidden" name="action" value="refuse_counter">
                  <button class="danger">Refuser la contre-offre</button>
                </form>
              </div>
            <?php elseif ($neg['status'] === 'acceptee'): ?>
              <p class="success-text">Accord conclu. Le produit est dans votre panier.</p>
            <?php elseif ($neg['status'] === 'refusee'): ?>
              <p class="danger-text">Négociation refusée.</p>
            <?php else: ?>
              <p class="muted">En attente de réponse du vendeur.</p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
