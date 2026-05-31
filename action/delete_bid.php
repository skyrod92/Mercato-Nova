<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo "Vous devez être connecté.";
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$userId = $_SESSION['user_id'];

// On supprime la dernière enchère de CET utilisateur sur CE produit
$stmt = $pdo->prepare("DELETE FROM bids WHERE product_id = ? AND user_id = ? ORDER BY amount DESC LIMIT 1");
$stmt->execute([$productId, $userId]);

echo "success";
?>
