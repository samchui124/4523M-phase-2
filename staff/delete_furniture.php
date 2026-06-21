<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireStaff();

$errors  = [];
$success = '';

// -------------------------------------------------------
// Handle delete POST
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fid = (int)($_POST['fid'] ?? 0);

    if ($fid <= 0) {
        $errors[] = 'Invalid furniture ID.';
    } else {
        // Check no existing orders for this furniture
        $chk = mysqli_prepare($conn,
            "SELECT COUNT(*) AS cnt
             FROM OrderFurnitures of2
             JOIN Orders o ON of2.oid = o.oid
             WHERE of2.fid = ?");
        mysqli_stmt_bind_param($chk, 'i', $fid);
        mysqli_stmt_execute($chk);
        $cnt = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($chk))['cnt'] ?? 0);
        mysqli_stmt_close($chk);

        if ($cnt > 0) {
            $errors[] = "Furniture #$fid cannot be deleted because it has $cnt existing order(s). "
                      . "Remove or reassign those orders first.";
        } else {
            mysqli_begin_transaction($conn);
            try {
                // Delete FurnitureMaterials first
                $delFm = mysqli_prepare($conn, "DELETE FROM FurnitureMaterials WHERE fid = ?");
                mysqli_stmt_bind_param($delFm, 'i', $fid);
                mysqli_stmt_execute($delFm);
                mysqli_stmt_close($delFm);

                // Delete Furniture
                $delF = mysqli_prepare($conn, "DELETE FROM Furnitures WHERE fid = ?");
                mysqli_stmt_bind_param($delF, 'i', $fid);
                mysqli_stmt_execute($delF);
                mysqli_stmt_close($delF);

                mysqli_commit($conn);
                $success = "Furniture #$fid deleted successfully.";
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $errors[] = 'Delete failed: ' . $e->getMessage();
            }
        }
    }
}

// -------------------------------------------------------
// Load all furniture items with order count
// -------------------------------------------------------
$listSql = "SELECT f.fid, f.fname, f.fimage, f.fprice,
                   COUNT(DISTINCT of2.oid) AS order_count
            FROM Furnitures f
            LEFT JOIN OrderFurnitures of2 ON f.fid = of2.fid
            GROUP BY f.fid, f.fname, f.fimage, f.fprice
            ORDER BY f.fid ASC";
$listRes = mysqli_query($conn, $listSql);

$pageTitle  = 'Delete Furniture';
$navSection = 'staff';
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="page-header"><i class="bi bi-trash me-2"></i>Delete Furniture Product</h2>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success alert-auto-dismiss">
    <i class="bi bi-check-circle me-1"></i><?= h($success) ?>
</div>
<?php endif; ?>

<div class="table-wrapper">
<table class="table table-hover align-middle mb-0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Furniture Name</th>
            <th class="text-end">Price</th>
            <th class="text-center">Existing Orders</th>
            <th class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($f = mysqli_fetch_assoc($listRes)): ?>
    <tr>
        <td>#<?= $f['fid'] ?></td>
        <td>
            <img src="<?= furnitureImageUrl(BASE_URL, $f['fimage']) ?>"
                 alt="<?= h($f['fname']) ?>" class="img-thumb">
        </td>
        <td class="fw-semibold"><?= h($f['fname']) ?></td>
        <td class="text-end"><?= money((float)$f['fprice']) ?></td>
        <td class="text-center">
            <?php if ((int)$f['order_count'] > 0): ?>
            <span class="badge bg-warning text-dark"><?= $f['order_count'] ?> order(s)</span>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
        </td>
        <td class="text-center">
            <?php if ((int)$f['order_count'] === 0): ?>
            <form method="post" action=""
                  data-confirm="Delete furniture &quot;<?= h($f['fname']) ?>&quot; (#<?= $f['fid'] ?>)? This cannot be undone.">
                <input type="hidden" name="fid" value="<?= $f['fid'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
            <?php else: ?>
            <span class="text-muted small" title="Cannot delete: existing orders">
                <i class="bi bi-lock"></i> Cannot delete
            </span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<p class="text-muted small mt-2">
    <i class="bi bi-info-circle me-1"></i>
    A furniture item can only be deleted when it has no existing related orders.
</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
