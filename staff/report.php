<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireStaff();

// -------------------------------------------------------
// Report: total orders per furniture item
// -------------------------------------------------------
$sql = "SELECT
            o.oid,
            f.fname,
            f.fimage,
            SUM(of2.oqty)       AS total_qty,
            SUM(o.ototalamount) AS total_sales
        FROM Orders o
        JOIN OrderFurnitures of2 ON o.oid  = of2.oid
        JOIN Furnitures f        ON of2.fid = f.fid
        WHERE o.ostatus IN (1, 2)
        GROUP BY o.oid, f.fid, f.fname, f.fimage
        ORDER BY o.oid ASC";

$result = mysqli_query($conn, $sql);

// Totals
$grandQty   = 0;
$grandSales = 0.0;
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $grandQty   += (int)$row['total_qty'];
    $grandSales += (float)$row['total_sales'];
    $rows[] = $row;
}

$pageTitle  = 'Sales Report';
$navSection = 'staff';
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="page-header"><i class="bi bi-bar-chart me-2"></i>Sales Report</h2>
<p class="text-muted small">
    Showing all <strong>Open</strong> and <strong>Approved</strong> orders.
    Rejected orders are excluded.
</p>

<!-- Summary cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body text-center">
                <h3 class="fw-bold"><?= count($rows) ?></h3>
                <small>Total Order Lines</small>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body text-center">
                <h3 class="fw-bold"><?= $grandQty ?></h3>
                <small>Total Items Ordered</small>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card bg-warning text-dark shadow-sm">
            <div class="card-body text-center">
                <h3 class="fw-bold"><?= money($grandSales) ?></h3>
                <small>Total Sales Amount</small>
            </div>
        </div>
    </div>
</div>

<!-- Report table -->
<div class="table-wrapper">
<table class="table table-hover align-middle mb-0">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Furniture Image</th>
            <th>Furniture Name</th>
            <th class="text-end">Total Items</th>
            <th class="text-end">Total Sales Amount</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
    <tr>
        <td colspan="5" class="text-center text-muted py-4">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>No active orders found.
        </td>
    </tr>
    <?php else: ?>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><strong>#<?= $row['oid'] ?></strong></td>
        <td>
            <img src="<?= furnitureImageUrl(BASE_URL, $row['fimage']) ?>"
                 alt="<?= h($row['fname']) ?>" class="img-thumb">
        </td>
        <td><?= h($row['fname']) ?></td>
        <td class="text-end fw-semibold"><?= (int)$row['total_qty'] ?></td>
        <td class="text-end fw-semibold text-success"><?= money((float)$row['total_sales']) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="table-dark fw-bold">
        <td colspan="3" class="text-end">Grand Total</td>
        <td class="text-end"><?= $grandQty ?></td>
        <td class="text-end"><?= money($grandSales) ?></td>
    </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
