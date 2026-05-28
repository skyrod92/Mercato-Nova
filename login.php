<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'Identifiant ou mot de passe incorrect.';
    } elseif (!$user['is_verified']) {
        $error = 'Votre email doit être vérifié avant la connexion.';
    } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['flash'] = 'Connexion réussie.';
        header('Location: index.php');
        exit;
    }
}
include 'includes/header.php';
?>
<main class="auth-page">
  <form class="auth-card" method="post">
    <h1>Connexion</h1>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
    <label>Identifiant ou email</label>
    <input name="identifier" required>
    <label>Mot de passe</label>
    <input type="password" name="password" required>
    <button>Se connecter</button>
    <p>Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
  </form>
</main>
<?php include 'includes/footer.php'; ?>
