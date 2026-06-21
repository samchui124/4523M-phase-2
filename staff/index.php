<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireStaff();

// Quick stats for dashboard cards
$stats = [];
foreach ([
    'furniture_count' => "SELECT COUNT(*) c FROM Furnitures",
    'order_count'     => "SELECT COUNT(*) c FROM Orders",
    'material_count'  => "SELECT COUNT(*) c FROM Materials",
    'open_orders'     => "SELECT COUNT(*) c FROM Orders WHERE ostatus = 1",
] as $key => $sql) {
    $r = mysqli_query($conn, $sql);
    $stats[$key] = (int)(mysqli_fetch_assoc($r)['c'] ?? 0);
}

$pageTitle  = 'Staff Dashboard';
$navSection = 'staff';
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="page-header"><i class="bi bi-speedometer2 me-2"></i>Staff Dashboard</h2>
<p class="text-muted">Welcome back, <strong><?= h($_SESSION['staff_name'] ?? '') ?></strong>
    (<?= h($_SESSION['staff_role'] ?? '') ?>)</p>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Open Orders',    $stats['open_orders'],     'bg-warning text-dark', 'bi-hourglass-split'],
        ['Total Orders',   $stats['order_count'],     'bg-primary',           'bi-bag'],
        ['Furniture Items',$stats['furniture_count'], 'bg-success',           'bi-table'],
        ['Materials',      $stats['material_count'],  'bg-info text-dark',    'bi-boxes'],
    ] as [$label, $val, $bg, $icon]): ?>
    <div class="col-6 col-md-3">
        <div class="card text-white <?= $bg ?> shadow-sm">
            <div class="card-body text-center py-3">
                <i class="bi <?= $icon ?> fs-2"></i>
                <h3 class="fw-bold mt-1 mb-0"><?= $val ?></h3>
                <small><?= $label ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Quick links -->
<div class="row g-3">
    <?php foreach ([
        [BASE_URL . '/staff/manage_orders.php',   'bi-clipboard-check', 'Manage Orders',     'btn-outline-primary'],
        [BASE_URL . '/staff/insert_furniture.php','bi-table',           'Insert Furniture',  'btn-outline-success'],
        [BASE_URL . '/staff/insert_material.php', 'bi-boxes',           'Insert Material',   'btn-outline-info'],
        [BASE_URL . '/staff/report.php',          'bi-bar-chart',       'View Report',       'btn-outline-warning'],
        [BASE_URL . '/staff/delete_furniture.php','bi-trash',           'Delete Furniture',  'btn-outline-danger'],
    ] as [$url, $icon, $label, $btn]): ?>
    <div class="col-6 col-md-3">
        <a href="<?= $url ?>" class="btn <?= $btn ?> w-100 py-3">
            <i class="bi <?= $icon ?> fs-3 d-block mb-1"></i><?= $label ?>
        </a>
    </div>
    <?php endforeach; ?></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
