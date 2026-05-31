<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin('../login.php');

$negotiationId = (int)($_POST['negotiation_id'] ?? 0);
$action = $_POST['action'] ?? '';
$userId = (int)$_SESSION['user_id'];

if ($negotiationId <= 0) {
    die('Négociation invalide.');
}

$stmt = $pdo->prepare("
    SELECT n.*, p.name AS product_name, p.id AS product_id
    FROM negotiations n
    JOIN products p ON p.id = n.product_id
    WHERE n.id = ?
");
$stmt->execute([$negotiationId]);
$negociation = $stmt->fetch();

if (!$negociation) {
    die('Négociation introuvable.');
}

function addNegotiatedProductToCart(PDO $pdo, int $buyerId, int $productId, float $price): void {
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND status = 'panier' LIMIT 1");
    $stmt->execute([$buyerId]);
    $orderId = $stmt->fetchColumn();

    if (!$orderId) {
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, status, total) VALUES (?, 'panier', 0)");
        $stmt->execute([$buyerId]);
        $orderId = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("SELECT id, quantity FROM order_items WHERE order_id = ? AND product_id = ?");
    $stmt->execute([$orderId, $productId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE order_items SET quantity = quantity + 1, unit_price = ? WHERE id = ?");
        $stmt->execute([$price, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, 1, ?)");
        $stmt->execute([$orderId, $productId, $price]);
    }

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity * unit_price), 0) FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $total = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE orders SET total = ? WHERE id = ?");
    $stmt->execute([$total, $orderId]);
}

/*
  Actions vendeur :
  - accept
  - refuse
  - counter

  Actions acheteur :
  - accept_counter
  - refuse_counter
*/

if ((int)$negociation['seller_id'] === $userId) {

    if ($action === 'accept') {
        $finalPrice = (float)$negociation['amount'];

        $stmt = $pdo->prepare("
            UPDATE negotiations
            SET status = 'acceptee', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$negotiationId]);

        addNegotiatedProductToCart(
            $pdo,
            (int)$negociation['buyer_id'],
            (int)$negociation['product_id'],
            $finalPrice
        );

        notifyUser(
            (int)$negociation['buyer_id'],
            'Proposition acceptée',
            'Votre proposition de ' . formatPrice($finalPrice) . ' sur ' . $negociation['product_name'] . ' a été acceptée. Le produit a été ajouté à votre panier.'
        );

        $_SESSION['flash'] = 'Proposition acceptée. Le produit a été ajouté au panier de l’acheteur.';
        header('Location: ../notifications.php');
        exit;
    }

    if ($action === 'refuse') {
        $stmt = $pdo->prepare("
            UPDATE negotiations
            SET status = 'refusee', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$negotiationId]);

        notifyUser(
            (int)$negociation['buyer_id'],
            'Proposition refusée',
            'Votre proposition sur ' . $negociation['product_name'] . ' a été refusée par le vendeur.'
        );

        $_SESSION['flash'] = 'Proposition refusée.';
        header('Location: ../notifications.php');
        exit;
    }

    if ($action === 'counter') {
        $counterAmount = (float)($_POST['counter_amount'] ?? 0);
        $sellerResponse = trim($_POST['seller_response'] ?? '');

        if ($counterAmount <= 0) {
            die('Montant de contre-offre invalide.');
        }

        $stmt = $pdo->prepare("
            UPDATE negotiations
            SET status = 'contre_offre',
                counter_amount = ?,
                seller_response = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$counterAmount, $sellerResponse, $negotiationId]);

        notifyUser(
            (int)$negociation['buyer_id'],
            'Contre-offre reçue',
            'Le vendeur propose ' . formatPrice($counterAmount) . ' pour ' . $negociation['product_name'] . '.'
        );

        $_SESSION['flash'] = 'Contre-offre envoyée à l’acheteur.';
        header('Location: ../notifications.php');
        exit;
    }
}

if ((int)$negociation['buyer_id'] === $userId) {

    if ($action === 'accept_counter' && $negociation['status'] === 'contre_offre') {
        $finalPrice = (float)$negociation['counter_amount'];

        $stmt = $pdo->prepare("
            UPDATE negotiations
            SET status = 'acceptee',
                amount = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$finalPrice, $negotiationId]);

        addNegotiatedProductToCart(
            $pdo,
            $userId,
            (int)$negociation['product_id'],
            $finalPrice
        );

        notifyUser(
            (int)$negociation['seller_id'],
            'Contre-offre acceptée',
            'L’acheteur a accepté votre contre-offre de ' . formatPrice($finalPrice) . ' sur ' . $negociation['product_name'] . '.'
        );

        $_SESSION['flash'] = 'Contre-offre acceptée. Le produit a été ajouté à votre panier.';
        header('Location: ../notifications.php');
        exit;
    }

    if ($action === 'refuse_counter' && $negociation['status'] === 'contre_offre') {
        $stmt = $pdo->prepare("
            UPDATE negotiations
            SET status = 'refusee',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$negotiationId]);

        notifyUser(
            (int)$negociation['seller_id'],
            'Contre-offre refusée',
            'L’acheteur a refusé votre contre-offre sur ' . $negociation['product_name'] . '.'
        );

        $_SESSION['flash'] = 'Contre-offre refusée.';
        header('Location: ../notifications.php');
        exit;
    }
}

die('Action non autorisée.');
