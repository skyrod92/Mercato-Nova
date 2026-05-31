<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
if (!isLoggedIn()) {
    echo 'login_required';
    exit;
}
$productId = (int)($_POST['product_id'] ?? 0);
if ($productId > 0) {
    $check = $pdo->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?');
    $check->execute([$_SESSION['user_id'], $productId]);
    if ($check->fetch()) {
        $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND product_id = ?')->execute([$_SESSION['user_id'], $productId]);
        echo 'removed';
    } else {
        $pdo->prepare('INSERT INTO favorites (user_id, product_id) VALUES (?, ?)')->execute([$_SESSION['user_id'], $productId]);
        echo 'added';
    }
}
?>
