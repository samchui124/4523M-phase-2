<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireStaff();

$flashSuccess = flash('flash_success');
$flashError   = flash('flash_error');

// -------------------------------------------------------
// Load all orders with furniture + customer info
// Each row in result = one material per order
// We group by order in the view layer
// -------------------------------------------------------
$sql = "SELECT
            o.oid, o.odate, o.ototalamount, o.odeliveraddress, o.odeliverydate, o.ostatus,
            f.fid, f.fname, f.fimage, f.fprice,
            of2.oqty,
            c.cname, c.ctel,
            m.mid, m.mname, m.mqty AS mphyqty, m.mavlqty, m.munit,
            fm.pmqty,
            (fm.pmqty * of2.oqty) AS used_qty
        FROM Orders o
        JOIN OrderFurnitures of2 ON o.oid  = of2.oid
        JOIN Furnitures f        ON of2.fid = f.fid
        JOIN Customers c         ON o.cid   = c.cid
        JOIN FurnitureMaterials fm ON f.fid  = fm.fid
        JOIN Materials m          ON fm.mid  = m.mid
        ORDER BY o.oid DESC, m.mname ASC";

$result = mysqli_query($conn, $sql);

// Group rows by order ID
$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $oid = $row['oid'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'oid'            => $oid,
            'odate'          => $row['odate'],
            'ototalamount'   => $row['ototalamount'],
            'odeliveraddress'=> $row['odeliveraddress'],
            'odeliverydate'  => $row['odeliverydate'],
            'ostatus'        => $row['ostatus'],
            'fid'            => $row['fid'],
            'fname'          => $row['fname'],
            'fimage'         => $row['fimage'],
            'fprice'         => $row['fprice'],
            'oqty'           => $row['oqty'],
            'cname'          => $row['cname'],
            'ctel'           => $row['ctel'],
            'materials'      => [],
        ];
    }
    $orders[$oid]['materials'][] = [
        'mid'      => $row['mid'],
        'mname'    => $row['mname'],
        'mphyqty'  => $row['mphyqty'],
        'mavlqty'  => $row['mavlqty'],
        'munit'    => $row['munit'],
        'pmqty'    => $row['pmqty'],
        'used_qty' => $row['used_qty'],
    ];
}

$pageTitle  = 'Manage Orders';
$navSection = 'staff';
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="page-header"><i class="bi bi-clipboard-check me-2"></i>Manage Orders</h2>

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show alert-auto-dismiss">
    <?= h($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show alert-auto-dismiss">
    <?= h($flashError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (empty($orders)): ?>
<div class="alert alert-info">No orders found.</div>
<?php else: ?>

<?php foreach ($orders as $o): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white">
        <div>
            <strong>Order #<?= $o['oid'] ?></strong>
            <span class="text-muted ms-2 small"><?= h(date('Y-m-d H:i', strtotime($o['odate']))) ?></span>
        </div>
        <span class="badge <?= orderStatusBadge((int)$o['ostatus']) ?> fs-6">
            <?= orderStatusLabel((int)$o['ostatus']) ?>
        </span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- Furniture + Order info -->
            <div class="col-md-5">
                <div class="d-flex gap-3 align-items-start">
                    <img src="<?= furnitureImageUrl(BASE_URL, $o['fimage']) ?>"
                         alt="<?= h($o['fname']) ?>" class="img-medium flex-shrink-0">
                    <div>
                        <div class="fw-bold"><?= h($o['fname']) ?></div>
                        <small class="text-muted">Furniture ID: #<?= $o['fid'] ?></small>
                        <div class="mt-1">Qty: <strong><?= $o['oqty'] ?></strong></div>
                        <div>Total: <strong><?= money((float)$o['ototalamount']) ?></strong></div>
                    </div>
                </div>
            </div>
            <!-- Customer info -->
            <div class="col-md-4">
                <small class="text-muted d-block">Customer</small>
                <div class="fw-semibold"><?= h($o['cname']) ?></div>
                <div><i class="bi bi-telephone me-1"></i><?= h($o['ctel']) ?></div>
                <small class="text-muted d-block mt-2">Delivery Address</small>
                <div><?= h($o['odeliveraddress']) ?></div>
                <small class="text-muted d-block mt-1">Delivery Date</small>
                <div><?= h(date('Y-m-d H:i', strtotime($o['odeliverydate']))) ?></div>
            </div>
            <!-- Update form -->
            <div class="col-md-3">
                <form method="post" action="<?= BASE_URL ?>/staff/update_order.php"
                      data-confirm="Update Order #<?= $o['oid'] ?>?">
                    <input type="hidden" name="oid" value="<?= $o['oid'] ?>">
                    <input type="hidden" name="fid" value="<?= $o['fid'] ?>">
                    <input type="hidden" name="old_qty" value="<?= $o['oqty'] ?>">
                    <input type="hidden" name="old_status" value="<?= $o['ostatus'] ?>">
                    <input type="hidden" name="fprice" value="<?= h($o['fprice']) ?>">

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Quantity</label>
                        <input type="number" name="oqty" class="form-control form-control-sm"
                               value="<?= $o['oqty'] ?>" min="1" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="ostatus" class="form-select form-select-sm">
                            <?php foreach ([1 => 'Open', 2 => 'Approved', 3 => 'Rejected'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= (int)$o['ostatus'] === $v ? 'selected' : '' ?>>
                                <?= $l ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-save me-1"></i>Update
                    </button>
                </form>
            </div>
        </div>

        <!-- Materials sub-table -->
        <?php if (!empty($o['materials'])): ?>
        <div class="mt-3">
            <small class="text-muted fw-semibold">Materials Used in This Order</small>
            <table class="table table-sm mt-1 mb-0">
                <thead>
                    <tr class="table-light">
                        <th>Material Name</th>
                        <th>Used (this order)</th>
                        <th>Physical Qty</th>
                        <th>Available Qty</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($o['materials'] as $mat): ?>
                <tr class="material-row">
                    <td><?= h($mat['mname']) ?></td>
                    <td><?= $mat['used_qty'] ?></td>
                    <td><?= $mat['mphyqty'] ?></td>
                    <td><?= $mat['mavlqty'] ?></td>
                    <td><?= h($mat['munit']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
