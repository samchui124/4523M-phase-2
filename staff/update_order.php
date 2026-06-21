<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireStaff();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/staff/manage_orders.php');
    exit;
}

$oid        = (int)($_POST['oid']        ?? 0);
$fid        = (int)($_POST['fid']        ?? 0);
$newQty     = (int)($_POST['oqty']       ?? 0);
$newStatus  = (int)($_POST['ostatus']    ?? 1);
$oldQty     = (int)($_POST['old_qty']    ?? 0);
$oldStatus  = (int)($_POST['old_status'] ?? 1);
$fprice     = (float)($_POST['fprice']   ?? 0);

if ($oid <= 0 || $fid <= 0 || $newQty <= 0) {
    setError('Invalid update request.');
    header('Location: ' . BASE_URL . '/staff/manage_orders.php');
    exit;
}

if (!in_array($newStatus, [1, 2, 3])) {
    setError('Invalid order status.');
    header('Location: ' . BASE_URL . '/staff/manage_orders.php');
    exit;
}

mysqli_begin_transaction($conn);

try {
    // -------------------------------------------------------
    // Determine stock adjustment required
    //
    // "Active" = status 1 (Open) or 2 (Approved) – stock is consumed
    // "Inactive" = status 3 (Rejected) – stock is free
    // -------------------------------------------------------
    $wasActive = in_array($oldStatus, [1, 2]);
    $isActive  = in_array($newStatus, [1, 2]);

    if ($wasActive && $isActive) {
        // Both active: apply quantity delta
        $delta = $newQty - $oldQty;
        if ($delta > 0) {
            // Need more stock
            $check = checkMaterialStock($conn, $fid, $delta);
            if (!$check['ok']) {
                throw new RuntimeException('Insufficient material stock: ' . implode('; ', $check['errors']));
            }
            deductMaterials($conn, $fid, $delta);
        } elseif ($delta < 0) {
            restoreMaterials($conn, $fid, abs($delta));
        }
    } elseif ($wasActive && !$isActive) {
        // Active → Rejected: restore old quantity
        restoreMaterials($conn, $fid, $oldQty);
    } elseif (!$wasActive && $isActive) {
        // Rejected → Active: deduct new quantity
        $check = checkMaterialStock($conn, $fid, $newQty);
        if (!$check['ok']) {
            throw new RuntimeException('Insufficient material stock: ' . implode('; ', $check['errors']));
        }
        deductMaterials($conn, $fid, $newQty);
    }
    // !$wasActive && !$isActive (Rejected → Rejected): no stock change

    // Recalculate total amount
    $newTotal = round($fprice * $newQty, 2);

    // Update Orders
    $updO = mysqli_prepare($conn,
        "UPDATE Orders SET ototalamount = ?, ostatus = ? WHERE oid = ?");
    mysqli_stmt_bind_param($updO, 'dii', $newTotal, $newStatus, $oid);
    mysqli_stmt_execute($updO);
    mysqli_stmt_close($updO);

    // Update OrderFurnitures quantity
    $updOf = mysqli_prepare($conn,
        "UPDATE OrderFurnitures SET oqty = ? WHERE oid = ? AND fid = ?");
    mysqli_stmt_bind_param($updOf, 'iii', $newQty, $oid, $fid);
    mysqli_stmt_execute($updOf);
    mysqli_stmt_close($updOf);

    mysqli_commit($conn);

    setSuccess("Order #$oid updated successfully.");
} catch (Throwable $e) {
    mysqli_rollback($conn);
    setError('Update failed: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/staff/manage_orders.php');
exit;
