<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

$customerId = (int)$_SESSION['customer_id'];
$success = '';
$error   = '';

// Load current customer data
$stmtC = mysqli_prepare($conn,
    "SELECT cid, cname, ctel, caddr FROM Customers WHERE cid = ?");
mysqli_stmt_bind_param($stmtC, 'i', $customerId);
mysqli_stmt_execute($stmtC);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtC));
mysqli_stmt_close($stmtC);

if (!$customer) {
    session_destroy();
    header('Location: ' . BASE_URL . '/customer/login.php');
    exit;
}

// -------------------------------------------------------
// Handle form submission
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = trim($_POST['cpassword']  ?? '');
    $confirmPwd  = trim($_POST['cpassword2'] ?? '');
    $newTel      = trim($_POST['ctel']       ?? '');
    $newAddr     = trim($_POST['caddr']      ?? '');

    if ($newTel === '' || $newAddr === '') {
        $error = 'Contact number and address are required.';
    } elseif ($newPassword !== '' && $newPassword !== $confirmPwd) {
        $error = 'Passwords do not match.';
    } else {
        if ($newPassword !== '') {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn,
                "UPDATE Customers SET cpassword = ?, ctel = ?, caddr = ? WHERE cid = ?");
            mysqli_stmt_bind_param($stmt, 'sssi', $hashed, $newTel, $newAddr, $customerId);
        } else {
            $stmt = mysqli_prepare($conn,
                "UPDATE Customers SET ctel = ?, caddr = ? WHERE cid = ?");
            mysqli_stmt_bind_param($stmt, 'ssi', $newTel, $newAddr, $customerId);
        }

        if (mysqli_stmt_execute($stmt)) {
            $success = 'Profile updated successfully!';
            // Reload data
            mysqli_stmt_close($stmt);
            $stmtC2 = mysqli_prepare($conn,
                "SELECT cid, cname, ctel, caddr FROM Customers WHERE cid = ?");
            mysqli_stmt_bind_param($stmtC2, 'i', $customerId);
            mysqli_stmt_execute($stmtC2);
            $customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtC2));
            mysqli_stmt_close($stmtC2);
        } else {
            $error = 'Update failed. Please try again.';
            mysqli_stmt_close($stmt);
        }
    }
}

$pageTitle  = 'My Profile';
$navSection = 'customer';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-md-7 col-lg-6">
    <h2 class="page-header"><i class="bi bi-person-gear me-2"></i>My Profile</h2>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss">
        <?= h($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show alert-auto-dismiss">
        <?= h($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label fw-semibold">Customer ID</label>
                <input type="text" class="form-control" value="#<?= $customer['cid'] ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <input type="text" class="form-control" value="<?= h($customer['cname']) ?>" readonly>
                <div class="form-text">Username cannot be changed.</div>
            </div>

            <hr>
            <p class="text-muted small mb-3">
                <i class="bi bi-lock me-1"></i>Leave password fields blank to keep your current password.
            </p>

            <div class="mb-3">
                <label class="form-label fw-semibold">New Password</label>
                <input type="password" name="cpassword" class="form-control"
                       autocomplete="new-password">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Confirm New Password</label>
                <input type="password" name="cpassword2" class="form-control"
                       autocomplete="new-password">
            </div>

            <hr>

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Contact Number <span class="text-danger">*</span>
                </label>
                <input type="text" name="ctel" class="form-control"
                       value="<?= h($customer['ctel']) ?>" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">
                    Address <span class="text-danger">*</span>
                </label>
                <textarea name="caddr" class="form-control" rows="2"
                          required><?= h($customer['caddr']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-save me-1"></i>Save Changes
            </button>
        </form>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
