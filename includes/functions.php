<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function currentRole(): string {
    return $_SESSION['role'] ?? 'visiteur';
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array(currentRole(), $roles, true)) {
        http_response_code(403);
        die('Accès refusé.');
    }
}

function cartOrderId(): ?int {
    if (!isLoggedIn()) {
        return null;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND status = 'panier' LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function cartCount(): int {
    if (!isLoggedIn()) {
        return 0;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity), 0)
                           FROM orders o
                           JOIN order_items oi ON oi.order_id = o.id
                           WHERE o.user_id = ? AND o.status = 'panier'");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

function formatPrice(float $price): string {
    return number_format($price, 2, ',', ' ') . ' €';
}

function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function notifyUser(int $userId, string $title, string $content): void {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $title, $content]);
}
?>
