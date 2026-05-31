<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$stmt = $pdo->prepare("SELECT o.id, o.total, oi.quantity, oi.unit_price, p.name, p.icon
                       FROM orders o
                       JOIN order_items oi ON oi.order_id = o.id
                       JOIN products p ON p.id = oi.product_id
                       WHERE o.user_id = ? AND o.status = 'panier'");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();
$total = array_sum(array_map(fn($i) => $i['quantity'] * $i['unit_price'], $items));
include 'includes/header.php';
?>

<?php include 'includes/footer.php'; ?>
