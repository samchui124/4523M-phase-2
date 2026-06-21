<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle  = 'Welcome';
$navSection = '';
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero-section">
    <h1><i class="bi bi-house-heart me-2"></i>Furniture Shop</h1>
    <p class="mb-0">Quality furniture crafted with care – browse, order, and manage your home.</p>
</div>

<div class="row g-4 justify-content-center">
    <div class="col-md-5">
        <a href="<?= BASE_URL ?>/customer/login.php" class="text-decoration-none">
            <div class="card portal-card customer text-center">
                <div class="card-body">
                    <div class="portal-icon"><i class="bi bi-person-circle"></i></div>
                    <h3 class="fw-bold mb-2">Customer Portal</h3>
                    <p class="mb-3 opacity-75">Browse furniture, place orders, and manage your account.</p>
                    <span class="btn btn-light fw-semibold px-4">Enter as Customer</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-5">
        <a href="<?= BASE_URL ?>/staff/login.php" class="text-decoration-none">
            <div class="card portal-card staff text-center">
                <div class="card-body">
                    <div class="portal-icon"><i class="bi bi-person-badge"></i></div>
                    <h3 class="fw-bold mb-2">Staff Portal</h3>
                    <p class="mb-3 opacity-75">Manage products, orders, materials, and reports.</p>
                    <span class="btn btn-light fw-semibold px-4">Enter as Staff</span>
                </div>
            </div>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
