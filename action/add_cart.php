<?php
require_once '../includes/functions.php';
$id = (int)($_POST['product_id'] ?? 0);
if ($id > 0) {
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    $_SESSION['flash'] = 'Produit ajouté au panier.';
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../panier.php'));
exit;
?>
