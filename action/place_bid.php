<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo 'Vous devez être connecté.';
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$amount = round((float)($_POST['amount'] ?? 0), 2);
$userId = (int)$_SESSION['user_id'];

$maxDatabaseLimit = 99999999.99;

if ($amount > $maxDatabaseLimit) {
    echo "Le montant maximal est " . formatPrice($maxDatabaseLimit);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product || $product['sale_mode'] !== 'enchere') {
    echo 'Produit non valide pour enchère.';
    exit;
}

if (!empty($product['end_date']) && strtotime($product['end_date']) < time()) {
    echo 'Cette enchère est terminée.';
    exit;
}

$bidStmt = $pdo->prepare("SELECT MAX(amount) FROM bids WHERE product_id = ?");
$bidStmt->execute([$productId]);
$highestBid = (float)$bidStmt->fetchColumn();

$currentMax = $highestBid > 0
    ? $highestBid
    : (float)$product['price'];

$minRequired = round($currentMax + 1.00, 2);

if ($amount < $minRequired) {
    echo "Mise insuffisante. Minimum requis : " . formatPrice($minRequired);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO bids (product_id, user_id, amount) VALUES (?, ?, ?)");
$stmt->execute([$productId, $userId, $amount]);

notifyUser(
    $userId,
    'Enchère validée',
    'Votre enchère de ' . formatPrice($amount) . ' sur ' . $product['name'] . ' a été enregistrée.'
);

if (!empty($product['seller_id'])) {
    notifyUser(
        (int)$product['seller_id'],
        'Nouvelle enchère',
        'Une enchère de ' . formatPrice($amount) . ' a été placée sur ' . $product['name'] . '.'
    );
}

echo 'success';
?>
