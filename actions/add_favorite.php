<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin('../login.php');
$productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
if ($productId > 0) {
    $stmt = $pdo->prepare('INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)');
    $stmt->execute([$userId, $productId]);
    $_SESSION['flash'] = 'Produit ajouté aux favoris.';
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
exit;
?>
