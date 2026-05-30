<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$productId = (int)($_POST['product_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
if ($productId <= 0 || $rating < 1 || $rating > 5) { die("Avis invalide."); }
$stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->execute([$productId, $_SESSION['user_id'], $rating, $comment]);
header('Location: ../produit.php?id=' . $productId);
exit;
