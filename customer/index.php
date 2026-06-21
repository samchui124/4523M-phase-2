<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

// -------------------------------------------------------
// Sorting
// -------------------------------------------------------
// Sorting – explicit whitelist map for safety
$sortMap = [
    'fname'   => 'f.fname',
    'fprice'  => 'f.fprice',
    'mavlqty' => 'avl_qty',
];
$sortKey  = isset($sortMap[$_GET['sort'] ?? '']) ? ($_GET['sort'] ?? '') : 'fname';
$sortExpr = $sortMap[$sortKey];
$sort     = $sortKey; // used for UI highlighting
$dir      = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

$sql = "SELECT f.fid, f.fname, f.fdesc, f.fimage, f.fprice,
               COALESCE(SUM(fm.pmqty), 0) AS total_materials,
               COALESCE(MIN(FLOOR(m.mavlqty / fm.pmqty)), 0) AS avl_qty
        FROM Furnitures f
        LEFT JOIN FurnitureMaterials fm ON f.fid = fm.fid
        LEFT JOIN Materials m ON fm.mid = m.mid
        GROUP BY f.fid, f.fname, f.fdesc, f.fimage, f.fprice
        ORDER BY {$sortExpr} {$dir}";

$result = mysqli_query($conn, $sql);

// Flash messages
$flashSuccess = flash('flash_success');
$flashError   = flash('flash_error');

$pageTitle  = 'Browse Furniture';
$navSection = 'customer';
require_once __DIR__ . '/../includes/header.php';

function sortIcon(string $col, string $current, string $currentDir): string {
    if ($col !== $current) return '<i class="bi bi-arrow-down-up sort-icon"></i>';
    return $currentDir === 'asc'
        ? '<i class="bi bi-sort-up sort-icon"></i>'
        : '<i class="bi bi-sort-down sort-icon"></i>';
}
function sortUrl(string $col, string $current, string $currentDir): string {
    $d = ($col === $current && $currentDir === 'asc') ? 'desc' : 'asc';
    return '?sort=' . $col . '&dir=' . $d;
}
?>

<h2 class="page-header"><i class="bi bi-grid me-2"></i>Browse Furniture</h2>

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

<!-- Sort Controls -->
<div class="d-flex gap-2 flex-wrap align-items-center mb-3">
    <span class="fw-semibold me-2">Sort by:</span>
    <?php foreach (['fname' => 'Name', 'fprice' => 'Price', 'mavlqty' => 'Availability'] as $col => $label): ?>
    <a href="<?= sortUrl($col, $sort, $dir) ?>"
       class="btn btn-sm <?= $sort === $col ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <?= $label ?>
        <?= sortIcon($col, $sort, $dir) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
<?php while ($f = mysqli_fetch_assoc($result)):
    $avlQty    = (int)$f['avl_qty'];
    $isSoldOut = $avlQty <= 0;
?>
    <div class="col">
        <div class="card furniture-card position-relative">
            <?php if ($isSoldOut): ?>
            <span class="sold-out-badge">Sold Out</span>
            <?php endif; ?>

            <img src="<?= furnitureImageUrl(BASE_URL, $f['fimage']) ?>"
                 alt="<?= h($f['fname']) ?>" class="card-img-top"
                 <?= $isSoldOut ? 'style="filter:grayscale(80%)"' : '' ?>>

            <div class="card-body d-flex flex-column">
                <h5 class="card-title fw-bold"><?= h($f['fname']) ?></h5>
                <p class="card-text text-muted small mb-2"><?= h($f['fdesc']) ?></p>
                <p class="price-tag mb-1"><?= money((float)$f['fprice']) ?></p>

                <p class="small mb-3">
                    <?php if ($isSoldOut): ?>
                        <span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>Sold out</span>
                    <?php else: ?>
                        <span class="text-success"><i class="bi bi-check-circle me-1"></i>In stock
                            <span class="text-muted">(<?= $avlQty ?> available)</span>
                        </span>
                    <?php endif; ?>
                </p>

                <div class="mt-auto">
                    <p class="small text-muted mb-2">Furniture ID: #<?= $f['fid'] ?></p>
                    <?php if (!$isSoldOut): ?>
                    <a href="<?= BASE_URL ?>/customer/place_order.php?fid=<?= $f['fid'] ?>"
                       class="btn btn-primary w-100">
                        <i class="bi bi-cart-plus me-1"></i>Order Now
                    </a>
                    <?php else: ?>
                    <button class="btn btn-secondary w-100" disabled>
                        <i class="bi bi-ban me-1"></i>Sold Out
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
