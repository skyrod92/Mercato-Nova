<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mercato Nova</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <a class="logo" href="index.php">🛒 Mercato<span>Nova</span></a>
  <nav>
    <a href="index.php">Catalogue</a>
    <a href="favoris.php">Favoris</a>
    <?php if (isLoggedIn()): ?>
      <a href="mes_encheres.php">Mes enchères</a>
    <?php endif; ?>
    <a href="panier.php">Panier <strong><?= cartCount() ?></strong></a>
  </nav>
  <div class="account-zone">
    <?php if (isLoggedIn()): ?>
      <span class="hello">Bonjour <?= h($_SESSION['username']) ?></span>
      <a class="login-btn secondary-btn" href="logout.php">Déconnexion</a>
    <?php else: ?>
      <a class="login-btn" href="login.php">Connexion</a>
    <?php endif; ?>
  </div>
</header>
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash"><?= h($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
<?php endif; ?>
<script src="assets/script.js" defer></script>
</body>
