<?php
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        $redirect = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: index.php?page=login&redirect=' . urlencode($redirect));
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: index.php?page=home&error=forbidden');
        exit;
    }
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool
{
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
