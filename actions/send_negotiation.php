<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin('../login.php');

$productId = (int)($_POST['product_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$buyerId = (int)$_SESSION['user_id'];

if ($productId <= 0 || $amount <= 0) {
    die('Proposition invalide.');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product || $product['sale_mode'] !== 'negociation') {
    die('Produit non négociable.');
}

$sellerId = (int)($product['seller_id'] ?: 2);

if ($sellerId === $buyerId) {
    $_SESSION['flash'] = 'Vous ne pouvez pas négocier votre propre produit.';
    header('Location: ../produit.php?id=' . $productId);
    exit;
}

$neg = $pdo->prepare("
    INSERT INTO negotiations (product_id, buyer_id, seller_id, amount, status, updated_at)
    VALUES (?, ?, ?, ?, 'envoyee', NOW())
");
$neg->execute([$productId, $buyerId, $sellerId, $amount]);

notifyUser(
    $sellerId,
    'Nouvelle négociation',
    'Une proposition de ' . formatPrice($amount) . ' a été envoyée sur ' . $product['name'] . '.'
);

$_SESSION['flash'] = 'Proposition de négociation envoyée au vendeur.';
header('Location: ../produit.php?id=' . $productId);
exit;
?>
