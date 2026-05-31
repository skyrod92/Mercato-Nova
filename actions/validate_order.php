<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin('../login.php');

$userId = (int)$_SESSION['user_id'];
$fullName = trim($_POST['full_name'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');
$cardNumber = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');

if ($fullName === '' || $address === '' || $city === '' || $postalCode === '' || strlen($cardNumber) < 12) {
    die('Informations de paiement ou livraison invalides.');
}

$stmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND status = 'panier' LIMIT 1");
$stmt->execute([$userId]);
$orderId = $stmt->fetchColumn();
if (!$orderId) die('Aucun panier à valider.');

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
$countStmt->execute([$orderId]);
if ((int)$countStmt->fetchColumn() === 0) die('Panier vide.');

$totalStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity * unit_price), 0) FROM order_items WHERE order_id = ?");
$totalStmt->execute([$orderId]);
$total = (float)$totalStmt->fetchColumn();
$last4 = substr($cardNumber, -4);

$update = $pdo->prepare("UPDATE orders
                         SET status = 'validee', total = ?, full_name = ?, address = ?, city = ?, postal_code = ?, card_last4 = ?, validated_at = NOW()
                         WHERE id = ? AND user_id = ?");
$update->execute([$total, $fullName, $address, $city, $postalCode, $last4, $orderId, $userId]);

notifyUser($userId, 'Achat validé', 'Votre commande #' . $orderId . ' a été validée pour un total de ' . formatPrice($total) . '.');
$_SESSION['flash'] = 'Achat validé ! Votre commande apparaît dans Mes achats.';
header('Location: ../mes_achats.php');
exit;
?>
