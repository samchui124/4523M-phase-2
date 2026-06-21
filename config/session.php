<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require a customer to be logged in.
 * Redirects to customer login page if not authenticated.
 */
function requireCustomer(): void {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'customer') {
        header('Location: ' . BASE_URL . '/customer/login.php');
        exit;
    }
}

/**
 * Require a staff member to be logged in.
 * Redirects to staff login page if not authenticated.
 */
function requireStaff(): void {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
        header('Location: ' . BASE_URL . '/staff/login.php');
        exit;
    }
}

/**
 * Return and clear a flash message stored in the session.
 */
function flash(string $key): string {
    $msg = $_SESSION[$key] ?? '';
    unset($_SESSION[$key]);
    return $msg;
}

/**
 * Set a flash success message.
 */
function setSuccess(string $msg): void {
    $_SESSION['flash_success'] = $msg;
}

/**
 * Set a flash error message.
 */
function setError(string $msg): void {
    $_SESSION['flash_error'] = $msg;
}
