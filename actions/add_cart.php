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

$stmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND status = 'panier' LIMIT 1");
$stmt->execute([$userId]);
$orderId = $stmt->fetchColumn();

if (!$orderId) {
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, status, total) VALUES (?, 'panier', 0)");
    $stmt->execute([$userId]);
    $orderId = $pdo->lastInsertId();
}

$stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
$stmt->execute([$productId]);
$unitPrice = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id, quantity FROM order_items WHERE order_id = ? AND product_id = ?");
$stmt->execute([$orderId, $productId]);
$existingItem = $stmt->fetch();

if ($existingItem) {
    $newQty = $existingItem['quantity'] + 1;
    $stmt = $pdo->prepare("UPDATE order_items SET quantity = ? WHERE id = ?");
    $stmt->execute([$newQty, $existingItem['id']]);
} else {
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, 1, ?)");
    $stmt->execute([$orderId, $productId, $unitPrice]);
}

$stmt = $pdo->prepare("SELECT SUM(quantity * unit_price) FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$newTotal = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("UPDATE orders SET total = ? WHERE id = ?");
$stmt->execute([$newTotal, $orderId]);

echo "success";
?>
