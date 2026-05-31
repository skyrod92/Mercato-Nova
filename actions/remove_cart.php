<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin('../login.php');
$productId = (int)($_POST['product_id'] ?? 0);
$orderId = cartOrderId();
if ($productId > 0 && $orderId) {
    $pdo->prepare("DELETE FROM order_items WHERE order_id = ? AND product_id = ?")->execute([$orderId, $productId]);
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity * unit_price), 0) FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $pdo->prepare("UPDATE orders SET total = ? WHERE id = ?")->execute([(float)$stmt->fetchColumn(), $orderId]);
    $_SESSION['flash'] = 'Produit retiré du panier.';
}
header('Location: ../panier.php');
exit;
?>
