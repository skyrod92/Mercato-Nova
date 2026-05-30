<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$token = $_GET['token'] ?? '';
if ($token) {
    $stmt = $pdo->prepare('UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?');
    $stmt->execute([$token]);
    $_SESSION['flash'] = $stmt->rowCount() ? 'Email vérifié, vous pouvez vous connecter.' : 'Lien de vérification invalide.';
}
header('Location: login.php');
exit;
?>