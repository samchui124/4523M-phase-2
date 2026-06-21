<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect already-logged-in staff
if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff') {
    header('Location: ' . BASE_URL . '/staff/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sname    = trim($_POST['sname']     ?? '');
    $spassword = trim($_POST['spassword'] ?? '');

    if ($sname === '' || $spassword === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT sid, sname, spassword, srole FROM Staffs WHERE sname = ?");
        mysqli_bind_param($stmt, 's', $sname);
        mysqli_execute($stmt);
        $res  = mysqli_stmt_get_result($stmt);
        $staff = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($staff && password_verify($spassword, $staff['spassword'])) {
            session_regenerate_id(true);
            $_SESSION['user_type']  = 'staff';
            $_SESSION['staff_id']   = $staff['sid'];
            $_SESSION['staff_name'] = $staff['sname'];
            $_SESSION['staff_role'] = $staff['srole'];
            header('Location: ' . BASE_URL . '/staff/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$pageTitle  = 'Staff Login';
$navSection = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card card">
        <div class="card-header" style="background:#c0392b">
            <h4 class="mb-0"><i class="bi bi-person-badge me-2"></i>Staff Login</h4>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show alert-auto-dismiss">
                <?= h($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Staff Username</label>
                    <input type="text" name="sname" class="form-control"
                           value="<?= h($_POST['sname'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="spassword" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </button>
            </form>

            <p class="text-center mt-3 mb-0 small">
                <a href="<?= BASE_URL ?>/index.php">← Back to Home</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
