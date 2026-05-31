<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status = 'validee' ORDER BY validated_at DESC, created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
include 'includes/header.php';
?>

<?php include 'includes/footer.php'; ?>
