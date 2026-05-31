<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo "Login requis";
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
if ($productId <= 0) {
    echo "Produit invalide";
    exit;
}

$userId = $_SESSION['user_id'];

// 1. Récupérer ou créer la commande active (statut 'panier')
$stmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND status = 'panier' LIMIT 1");
$stmt->execute([$userId]);
$orderId = $stmt->fetchColumn();

if (!$orderId) {
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, status, total) VALUES (?, 'panier', 0)");
    $stmt->execute([$userId]);
    $orderId = $pdo->lastInsertId();
}

// 2. Récupérer le prix du produit
$stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
$stmt->execute([$productId]);
$unitPrice = (float)$stmt->fetchColumn();

// 3. VÉRIFICATION DU DOUBLON : Le produit est-il déjà dans ce panier ?
$stmt = $pdo->prepare("SELECT id, quantity FROM order_items WHERE order_id = ? AND product_id = ?");
$stmt->execute([$orderId, $productId]);
$existingItem = $stmt->fetch();

if ($existingItem) {
    // Si le produit existe déjà, on augmente la quantité de 1
    $newQty = $existingItem['quantity'] + 1;
    $stmt = $pdo->prepare("UPDATE order_items SET quantity = ? WHERE id = ?");
    $stmt->execute([$newQty, $existingItem['id']]);
} else {
    // Si le produit n'existe pas, on crée une nouvelle ligne
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, 1, ?)");
    $stmt->execute([$orderId, $productId, $unitPrice]);
}

// 4. Mettre à jour le total de la commande globale
$stmt = $pdo->prepare("SELECT SUM(quantity * unit_price) FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$newTotal = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("UPDATE orders SET total = ? WHERE id = ?");
$stmt->execute([$newTotal, $orderId]);

echo "success";
?>
