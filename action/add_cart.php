<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$productId = (int)($_POST['product_id'] ?? 0);
if ($productId > 0) {
    if (!isLoggedIn()) {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
        $stmt->execute([$_SESSION['user_id'], $productId]);
    }
}
?>
