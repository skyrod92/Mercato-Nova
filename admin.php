<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireRole(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
    $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$_POST['role'], (int)$_POST['user_id']]);
    $_SESSION['flash'] = 'Rôle utilisateur mis à jour.';
    header('Location: admin.php');
    exit;
}
if (isset($_GET['delete_product'])) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int)$_GET['delete_product']]);
    $_SESSION['flash'] = 'Produit modéré/supprimé.';
    header('Location: admin.php');
    exit;
}
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$products = $pdo->query("SELECT p.*, u.username AS seller_name FROM products p LEFT JOIN users u ON u.id=p.seller_id ORDER BY p.created_at DESC")->fetchAll();
include 'includes/header.php';
?>
<main class="page">
  <h1>Administration</h1>
  <section class="section-box">
    <h2>Gestion des utilisateurs</h2>
    <?php foreach ($users as $u): ?>
      <form class="history-line" method="post">
        <span><?= h($u['username']) ?> — <?= h($u['email']) ?></span>
        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
        <select name="role"><option <?= $u['role']==='acheteur'?'selected':'' ?> value="acheteur">Acheteur</option><option <?= $u['role']==='vendeur'?'selected':'' ?> value="vendeur">Vendeur</option><option <?= $u['role']==='admin'?'selected':'' ?> value="admin">Admin</option></select>
        <button>Modifier</button>
      </form>
    <?php endforeach; ?>
  </section>
  <section class="section-box">
    <h2>Modération des produits</h2>
    <?php foreach ($products as $p): ?>
      <div class="history-line"><span><?= h($p['icon'].' '.$p['name']) ?> — vendeur : <?= h($p['seller_name'] ?? 'Mercato Nova') ?></span><a class="btn danger" href="admin.php?delete_product=<?= (int)$p['id'] ?>" onclick="return confirm('Supprimer ce produit ?')">Supprimer</a></div>
    <?php endforeach; ?>
  </section>
</main>
<?php include 'includes/footer.php'; ?>
