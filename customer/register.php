<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cname    = trim($_POST['cname']    ?? '');
    $cpassword = trim($_POST['cpassword'] ?? '');
    $cpassword2 = trim($_POST['cpassword2'] ?? '');
    $ctel    = trim($_POST['ctel']    ?? '');
    $caddr   = trim($_POST['caddr']   ?? '');
    $company = trim($_POST['company'] ?? '');

    if ($cname === '' || $cpassword === '' || $ctel === '' || $caddr === '') {
        $error = 'All required fields must be filled in.';
    } elseif ($cpassword !== $cpassword2) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate username
        $chk = mysqli_prepare($conn, "SELECT cid FROM Customers WHERE cname = ?");
        mysqli_bind_param($chk, 's', $cname);
        mysqli_execute($chk);
        mysqli_stmt_store_result($chk);
        $exists = mysqli_stmt_num_rows($chk) > 0;
        mysqli_stmt_close($chk);

        if ($exists) {
            $error = 'Username already taken. Please choose another.';
        } else {
            $comp = $company !== '' ? $company : null;
            $ins = mysqli_prepare($conn,
                "INSERT INTO Customers (cname, cpassword, ctel, caddr, company)
                 VALUES (?, ?, ?, ?, ?)");
            mysqli_bind_param($ins, 'sssss', $cname, $cpassword, $ctel, $caddr, $comp);
            if (mysqli_execute($ins)) {
                $success = 'Account created! You can now log in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            mysqli_stmt_close($ins);
        }
    }
}

$pageTitle  = 'Customer Registration';
$navSection = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card card" style="max-width:500px">
        <div class="card-header">
            <h4 class="mb-0"><i class="bi bi-person-plus me-2"></i>Create Account</h4>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show alert-auto-dismiss">
                <?= h($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?= h($success) ?>
                <a href="<?= BASE_URL ?>/customer/login.php" class="btn btn-sm btn-success ms-2">Login now</a>
            </div>
            <?php else: ?>
            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                    <input type="text" name="cname" class="form-control"
                           value="<?= h($_POST['cname'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <input type="password" name="cpassword" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="cpassword2" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Contact Number <span class="text-danger">*</span></label>
                    <input type="text" name="ctel" class="form-control"
                           value="<?= h($_POST['ctel'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                    <textarea name="caddr" class="form-control" rows="2" required><?= h($_POST['caddr'] ?? '') ?></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Company (optional)</label>
                    <input type="text" name="company" class="form-control"
                           value="<?= h($_POST['company'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-person-check me-1"></i>Register
                </button>
            </form>
            <?php endif; ?>
            <p class="text-center mt-3 mb-0 small">
                Already have an account? <a href="<?= BASE_URL ?>/customer/login.php">Login</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
