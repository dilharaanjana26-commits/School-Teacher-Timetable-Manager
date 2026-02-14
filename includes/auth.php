<?php

declare(strict_types=1);

function isLoggedIn(): bool
{
    return isset($_SESSION['admin_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}
