<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect already-logged-in customers
if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer') {
    header('Location: ' . BASE_URL . '/customer/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cname    = trim($_POST['cname']    ?? '');
    $cpassword = trim($_POST['cpassword'] ?? '');

    if ($cname === '' || $cpassword === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT cid, cname, cpassword FROM Customers WHERE cname = ?");
        mysqli_bind_param($stmt, 's', $cname);
        mysqli_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $customer = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($customer && $customer['cpassword'] === $cpassword) {
            session_regenerate_id(true);
            $_SESSION['user_type']     = 'customer';
            $_SESSION['customer_id']   = $customer['cid'];
            $_SESSION['customer_name'] = $customer['cname'];
            header('Location: ' . BASE_URL . '/customer/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$pageTitle  = 'Customer Login';
$navSection = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card card">
        <div class="card-header">
            <h4 class="mb-0"><i class="bi bi-person-circle me-2"></i>Customer Login</h4>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show alert-auto-dismiss" role="alert">
                <?= h($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <input type="text" name="cname" class="form-control"
                           value="<?= h($_POST['cname'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="cpassword" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </button>
            </form>

            <hr class="my-3">
            <p class="text-center mb-0 small">
                Don't have an account?
                <a href="<?= BASE_URL ?>/customer/register.php">Register here</a>
            </p>
            <p class="text-center mb-0 small mt-1">
                <a href="<?= BASE_URL ?>/index.php">← Back to Home</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
