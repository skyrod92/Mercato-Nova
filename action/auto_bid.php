<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin('../login.php');

$productId = (int)($_POST['product_id'] ?? 0);
$maxAmount = round((float)($_POST['max_amount'] ?? 0), 2);
$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product || $product['sale_mode'] !== 'enchere' || $maxAmount <= 0) {
    die('Enchère automatique invalide.');
}

$save = $pdo->prepare("
    INSERT INTO auto_bids (product_id, user_id, max_amount)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE max_amount = VALUES(max_amount)
");
$save->execute([$productId, $userId, $maxAmount]);

$currentStmt = $pdo->prepare("SELECT MAX(amount) FROM bids WHERE product_id = ?");
$currentStmt->execute([$productId]);
$current = (float)$currentStmt->fetchColumn();

$nextBid = max((float)$product['price'], $current) + 1;

if ($nextBid <= $maxAmount) {
    $bid = $pdo->prepare("
        INSERT INTO bids (product_id, user_id, amount)
        VALUES (?, ?, ?)
    ");
    $bid->execute([$productId, $userId, $nextBid]);
}

notifyUser(
    $userId,
    'Enchère automatique activée',
    'Votre enchère automatique est active jusqu’à ' .
    formatPrice($maxAmount) .
    ' sur ' .
    $product['name'] .
    '.'
);

$_SESSION['flash'] = 'Enchère automatique placée.';

header('Location: ../produit.php?id=' . $productId);
exit;
?>
