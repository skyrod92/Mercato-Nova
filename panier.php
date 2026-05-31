<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();
$stmt = $pdo->prepare("SELECT p.*, c.quantity FROM cart_items c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();
$total = 0;
include 'includes/header.php';
?>

<?php include 'includes/footer.php'; ?>
