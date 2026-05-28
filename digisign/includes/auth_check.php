<?php
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }
}

function requireOtpVerified(): void
{
    requireLogin();
    if (empty($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== 1) {
        header('Location: index.php?page=setup_otp');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: index.php?page=home');
        exit;
    }
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
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