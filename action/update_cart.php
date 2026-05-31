<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
if (!isLoggedIn()) exit;

$productId = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);
$userId = (int)$_SESSION['user_id'];
if ($productId <= 0) exit;

$orderId = cartOrderId();
if (!$orderId) exit;

if ($quantity <= 0) {
    $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ? AND product_id = ?");
    $stmt->execute([$orderId, $productId]);
} else {
    $stmt = $pdo->prepare("UPDATE order_items SET quantity = ? WHERE order_id = ? AND product_id = ?");
    $stmt->execute([$quantity, $orderId, $productId]);
}

$stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity * unit_price), 0) FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$total = (float)$stmt->fetchColumn();
$pdo->prepare("UPDATE orders SET total = ? WHERE id = ?")->execute([$total, $orderId]);
echo 'success';
?>
