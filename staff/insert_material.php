<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireStaff();

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mname  = trim($_POST['mname']  ?? '');
    $mqty   = trim($_POST['mqty']   ?? '');
    $munit  = trim($_POST['munit']  ?? '');

    if ($mname === '') $errors[] = 'Material name is required.';
    if (!ctype_digit($mqty) || (int)$mqty < 0) $errors[] = 'Physical quantity must be a non-negative integer.';
    if ($munit === '') $errors[] = 'Unit is required.';

    if (empty($errors)) {
        $mqty = (int)$mqty;
        // Available quantity starts equal to physical quantity
        $stmt = mysqli_prepare($conn,
            "INSERT INTO Materials (mname, mqty, mavlqty, munit) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'siis', $mname, $mqty, $mqty, $munit);
        if (mysqli_stmt_execute($stmt)) {
            $newMid  = (int)mysqli_insert_id($conn);
            $success = "Material #$newMid \"" . h($mname) . "\" added successfully!";
        } else {
            $errors[] = 'Failed to insert material. Please try again.';
        }
        mysqli_stmt_close($stmt);
    }
}

$pageTitle  = 'Insert Material';
$navSection = 'staff';
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="page-header"><i class="bi bi-boxes me-2"></i>Insert Material</h2>

<div class="row justify-content-center">
<div class="col-md-6">
    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success alert-auto-dismiss">
        <i class="bi bi-check-circle me-1"></i><?= $success ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Material Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="mname" class="form-control"
                       value="<?= h($_POST['mname'] ?? '') ?>" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Physical Quantity <span class="text-danger">*</span>
                </label>
                <input type="number" name="mqty" class="form-control"
                       min="0" value="<?= h($_POST['mqty'] ?? '0') ?>" required>
                <div class="form-text">
                    Available quantity will be set equal to physical quantity on creation.
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">
                    Unit <span class="text-danger">*</span>
                </label>
                <input type="text" name="munit" class="form-control" placeholder="e.g. pcs, meter, block"
                       value="<?= h($_POST['munit'] ?? '') ?>" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success flex-fill fw-semibold">
                    <i class="bi bi-save me-1"></i>Save Material
                </button>
                <a href="<?= BASE_URL ?>/staff/index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Existing materials list -->
    <div class="mt-4">
        <h5 class="fw-bold">Existing Materials</h5>
        <div class="table-wrapper">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>ID</th><th>Name</th><th>Physical Qty</th><th>Available Qty</th><th>Unit</th></tr></thead>
            <tbody>
            <?php
            $matRes = mysqli_query($conn, "SELECT mid, mname, mqty, mavlqty, munit FROM Materials ORDER BY mid");
            while ($m = mysqli_fetch_assoc($matRes)):
            ?>
            <tr>
                <td>#<?= $m['mid'] ?></td>
                <td><?= h($m['mname']) ?></td>
                <td><?= $m['mqty'] ?></td>
                <td><?= $m['mavlqty'] ?></td>
                <td><?= h($m['munit']) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
