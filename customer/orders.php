<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

$customerId = (int)$_SESSION['customer_id'];

// -------------------------------------------------------
// Sorting
// -------------------------------------------------------
$allowedSort = ['oid', 'odate', 'ototalamount', 'odeliverydate', 'ostatus'];
$sort = in_array($_GET['sort'] ?? '', $allowedSort) ? $_GET['sort'] : 'odate';
$dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

$sql = "SELECT o.oid, o.odate, f.fid, f.fname, of2.oqty,
               o.ototalamount, o.cid, o.odeliverydate, o.odeliveraddress, o.ostatus
        FROM Orders o
        JOIN OrderFurnitures of2 ON o.oid = of2.oid
        JOIN Furnitures f ON of2.fid = f.fid
        WHERE o.cid = ?
        ORDER BY o.{$sort} {$dir}";

$stmt = mysqli_prepare($conn, $sql);
mysqli_bind_param($stmt, 'i', $customerId);
mysqli_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$flashSuccess = flash('flash_success');
$flashError   = flash('flash_error');

$pageTitle  = 'My Orders';
$navSection = 'customer';
require_once __DIR__ . '/../includes/header.php';

function colLink(string $col, string $label, string $currentSort, string $currentDir): string {
    $d = ($col === $currentSort && $currentDir === 'asc') ? 'desc' : 'asc';
    $icon = ($col !== $currentSort)
        ? '<i class="bi bi-arrow-down-up sort-icon"></i>'
        : ($currentDir === 'asc' ? '<i class="bi bi-sort-up sort-icon"></i>' : '<i class="bi bi-sort-down sort-icon"></i>');
    return "<a href='?sort={$col}&dir={$d}' class='sort-link'>{$label} {$icon}</a>";
}
?>

<h2 class="page-header"><i class="bi bi-bag me-2"></i>My Orders</h2>

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show alert-auto-dismiss">
    <?= h($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show alert-auto-dismiss">
    <?= $flashError ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="table-wrapper">
<table class="table table-hover align-middle mb-0">
    <thead>
        <tr>
            <th><?= colLink('oid',          'Order ID',       $sort, $dir) ?></th>
            <th><?= colLink('odate',         'Order Date',     $sort, $dir) ?></th>
            <th>Furniture</th>
            <th>Qty</th>
            <th><?= colLink('ototalamount',  'Total Amount',   $sort, $dir) ?></th>
            <th>Customer ID</th>
            <th><?= colLink('odeliverydate', 'Delivery Date',  $sort, $dir) ?></th>
            <th><?= colLink('ostatus',       'Status',         $sort, $dir) ?></th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $today = new DateTimeImmutable('today');
    $hasOrders = false;
    while ($row = mysqli_fetch_assoc($result)):
        $hasOrders = true;
        $deliveryDt = new DateTimeImmutable($row['odeliverydate']);
        $daysToDelivery = (int)$today->diff($deliveryDt)->format('%r%a');
        $canDelete = $daysToDelivery >= 2;
    ?>
    <tr>
        <td><strong>#<?= $row['oid'] ?></strong></td>
        <td><?= h(date('Y-m-d H:i', strtotime($row['odate']))) ?></td>
        <td>
            <span class="fw-semibold"><?= h($row['fname']) ?></span>
            <small class="text-muted d-block">ID: #<?= $row['fid'] ?></small>
        </td>
        <td><?= (int)$row['oqty'] ?></td>
        <td><?= money((float)$row['ototalamount']) ?></td>
        <td>#<?= $row['cid'] ?></td>
        <td><?= h(date('Y-m-d H:i', strtotime($row['odeliverydate']))) ?></td>
        <td>
            <span class="badge <?= orderStatusBadge((int)$row['ostatus']) ?>">
                <?= orderStatusLabel((int)$row['ostatus']) ?>
            </span>
        </td>
        <td>
            <?php if ($canDelete): ?>
            <form method="post" action="<?= BASE_URL ?>/customer/delete_order.php"
                  data-confirm="Delete Order #<?= $row['oid'] ?>? This cannot be undone.">
                <input type="hidden" name="oid" value="<?= $row['oid'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
            <?php else: ?>
            <span class="text-muted small" title="Can only delete ≥2 days before delivery">
                <i class="bi bi-lock"></i> Locked
            </span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
    <?php if (!$hasOrders): ?>
    <tr>
        <td colspan="9" class="text-center text-muted py-4">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>No orders found.
            <a href="<?= BASE_URL ?>/customer/index.php" class="btn btn-sm btn-primary mt-2">
                Browse Furniture
            </a>
        </td>
    </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<p class="text-muted small mt-2">
    <i class="bi bi-info-circle me-1"></i>
    Orders can only be deleted when today is at least 2 days before the delivery date.
</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
