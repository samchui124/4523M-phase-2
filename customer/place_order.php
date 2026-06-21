<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

$customerId = (int)$_SESSION['customer_id'];
$fid        = (int)($_GET['fid'] ?? 0);
$error      = '';

// -------------------------------------------------------
// Load furniture item
// -------------------------------------------------------
if ($fid <= 0) {
    setError('No furniture item selected.');
    header('Location: ' . BASE_URL . '/customer/index.php');
    exit;
}

$stmtF = mysqli_prepare($conn,
    "SELECT f.fid, f.fname, f.fdesc, f.fimage, f.fprice,
            MIN(FLOOR(m.mavlqty / fm.pmqty)) AS avl_qty
     FROM Furnitures f
     JOIN FurnitureMaterials fm ON f.fid = fm.fid
     JOIN Materials m ON fm.mid = m.mid
     WHERE f.fid = ?
     GROUP BY f.fid, f.fname, f.fdesc, f.fimage, f.fprice");
mysqli_bind_param($stmtF, 'i', $fid);
mysqli_execute($stmtF);
$furniture = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtF));
mysqli_stmt_close($stmtF);

if (!$furniture) {
    setError('Furniture item not found.');
    header('Location: ' . BASE_URL . '/customer/index.php');
    exit;
}

if ((int)$furniture['avl_qty'] <= 0) {
    setError('This item is currently out of stock.');
    header('Location: ' . BASE_URL . '/customer/index.php');
    exit;
}

// -------------------------------------------------------
// Handle form submission
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oqty          = (int)($_POST['oqty'] ?? 0);
    $deliverAddr   = trim($_POST['odeliveraddress'] ?? '');
    $deliverDate   = trim($_POST['odeliverydate']   ?? '');
    $orderDate     = date('Y-m-d H:i:s');

    // Validation
    if ($oqty <= 0) {
        $error = 'Order quantity must be at least 1.';
    } elseif ($deliverAddr === '') {
        $error = 'Delivery address is required.';
    } elseif ($deliverDate === '') {
        $error = 'Delivery date is required.';
    } elseif (strtotime($deliverDate) <= strtotime('today')) {
        $error = 'Delivery date must be tomorrow or later.';
    } else {
        // Check material stock
        $check = checkMaterialStock($conn, $fid, $oqty);
        if (!$check['ok']) {
            $error = 'Cannot place order: ' . implode('; ', $check['errors']);
        } else {
            // Calculate total amount
            $totalAmount = round((float)$furniture['fprice'] * $oqty, 2);

            // Begin transaction
            mysqli_begin_transaction($conn);
            try {
                // Insert Order
                $insOrder = mysqli_prepare($conn,
                    "INSERT INTO Orders (odate, ototalamount, cid, odeliverydate, odeliveraddress, ostatus)
                     VALUES (?, ?, ?, ?, ?, 1)");
                mysqli_bind_param($insOrder, 'sdiss',
                    $orderDate, $totalAmount, $customerId, $deliverDate, $deliverAddr);
                mysqli_execute($insOrder);
                $newOid = (int)mysqli_insert_id($conn);
                mysqli_stmt_close($insOrder);

                // Insert OrderFurnitures
                $insOf = mysqli_prepare($conn,
                    "INSERT INTO OrderFurnitures (oid, fid, oqty) VALUES (?, ?, ?)");
                mysqli_bind_param($insOf, 'iii', $newOid, $fid, $oqty);
                mysqli_execute($insOf);
                mysqli_stmt_close($insOf);

                // Deduct material available quantities
                deductMaterials($conn, $fid, $oqty);

                mysqli_commit($conn);

                setSuccess("Order #$newOid placed successfully!");
                header('Location: ' . BASE_URL . '/customer/orders.php');
                exit;
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $error = 'Order failed. Please try again.';
            }
        }
    }
}

$pageTitle  = 'Place Order';
$navSection = 'customer';
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="page-header"><i class="bi bi-cart-plus me-2"></i>Place Order</h2>

<div class="row g-4">
    <!-- Furniture summary -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <img src="<?= furnitureImageUrl(BASE_URL, $furniture['fimage']) ?>"
                 alt="<?= h($furniture['fname']) ?>"
                 class="card-img-top" style="height:220px;object-fit:cover">
            <div class="card-body">
                <h5 class="fw-bold"><?= h($furniture['fname']) ?></h5>
                <p class="text-muted small mb-2"><?= h($furniture['fdesc']) ?></p>
                <p class="mb-1"><strong>Furniture ID:</strong> #<?= $furniture['fid'] ?></p>
                <p class="mb-1"><strong>Price:</strong>
                    <span class="price-tag"><?= money((float)$furniture['fprice']) ?></span>
                </p>
                <p class="mb-0 text-success small">
                    <i class="bi bi-check-circle me-1"></i>In stock
                    (<?= (int)$furniture['avl_qty'] ?> available)
                </p>
            </div>
        </div>
    </div>

    <!-- Order form -->
    <div class="col-md-8">
        <div class="form-card">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show alert-auto-dismiss">
                <?= h($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="post" action="">
                <!-- Hidden price for JS calc -->
                <input type="hidden" id="fprice" value="<?= h($furniture['fprice']) ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Customer ID</label>
                    <input type="text" class="form-control" value="#<?= $customerId ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Order Date</label>
                    <input type="text" class="form-control" value="<?= date('Y-m-d H:i:s') ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Order Quantity <span class="text-danger">*</span>
                    </label>
                    <input type="number" id="oqty" name="oqty" class="form-control"
                           min="1" max="<?= (int)$furniture['avl_qty'] ?>"
                           value="<?= (int)($_POST['oqty'] ?? 1) ?>" required>
                    <div class="form-text">Maximum: <?= (int)$furniture['avl_qty'] ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Total Order Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">HK$</span>
                        <input type="text" class="form-control fw-bold text-primary"
                               id="total_display" readonly>
                    </div>
                    <div class="form-text">Calculated automatically.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Delivery Address <span class="text-danger">*</span>
                    </label>
                    <textarea name="odeliveraddress" class="form-control" rows="2"
                              required><?= h($_POST['odeliveraddress'] ?? '') ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Delivery Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="odeliverydate" class="form-control"
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           value="<?= h($_POST['odeliverydate'] ?? '') ?>" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill fw-semibold">
                        <i class="bi bi-bag-check me-1"></i>Confirm Order
                    </button>
                    <a href="<?= BASE_URL ?>/customer/index.php" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
