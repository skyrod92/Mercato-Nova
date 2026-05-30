<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$error = '';
$debugLink = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $token = bin2hex(random_bytes(32));
        try {
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, verification_token) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $token]);
            $verifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/verify.php?token=' . $token;
            @mail($email, 'Verification Mercato Nova', "Cliquez ici pour vérifier votre compte : $verifyUrl");
            $debugLink = $verifyUrl;
            $_SESSION['flash'] = 'Compte créé. Vérifiez votre email pour activer le compte.';
        } catch (PDOException $e) {
            $error = 'Identifiant ou email déjà utilisé.';
        }
    }
}
include 'includes/header.php';
?>
<main class="auth-page">
  <form class="auth-card" method="post">
    <h1>Inscription</h1>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
    <?php if ($debugLink): ?><p class="success">Lien de vérification local : <a href="<?= h($debugLink) ?>">vérifier mon compte</a></p><?php endif; ?>
    <label>Identifiant</label>
    <input name="username" required minlength="3">
    <label>Email</label>
    <input type="email" name="email" required>
    <label>Mot de passe</label>
    <input type="password" name="password" required minlength="8">
    <label>Confirmer le mot de passe</label>
    <input type="password" name="confirm_password" required minlength="8">
    <button>Créer le compte</button>
    <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
  </form>
</main>
<?php include 'includes/footer.php'; ?>