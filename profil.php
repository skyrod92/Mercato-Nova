<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();
$error = '';
$userId = (int)$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informations invalides.';
    } else {
        if ($password !== '') {
            if (strlen($password) < 8) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password_hash=? WHERE id=?");
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $userId]);
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=? WHERE id=?");
            $stmt->execute([$username, $email, $userId]);
        }
        if (!$error) {
            $_SESSION['username'] = $username;
            $_SESSION['flash'] = 'Profil mis à jour.';
            header('Location: profil.php');
            exit;
        }
    }
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
include 'includes/header.php';
?>
<main class="auth-page">
  <form class="auth-card" method="post">
    <h1>Mon profil</h1>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
    <label>Identifiant</label><input name="username" value="<?= h($user['username']) ?>" required>
    <label>Email</label><input type="email" name="email" value="<?= h($user['email']) ?>" required>
    <label>Nouveau mot de passe</label><input type="password" name="password" placeholder="Laisser vide pour ne pas changer">
    <p>Rôle : <strong><?= h($user['role']) ?></strong></p>
    <button>Modifier mon profil</button>
  </form>
</main>
<?php include 'includes/footer.php'; ?>
