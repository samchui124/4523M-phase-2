<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

$customerId = (int)$_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/customer/orders.php');
    exit;
}

$oid = (int)($_POST['oid'] ?? 0);

if ($oid <= 0) {
    setError('Invalid order.');
    header('Location: ' . BASE_URL . '/customer/orders.php');
    exit;
}

// -------------------------------------------------------
// Load the order (verify it belongs to this customer)
// -------------------------------------------------------
$stmtO = mysqli_prepare($conn,
    "SELECT o.oid, o.odeliverydate, o.ostatus, of2.fid, of2.oqty
     FROM Orders o
     JOIN OrderFurnitures of2 ON o.oid = of2.oid
     WHERE o.oid = ? AND o.cid = ?");
mysqli_stmt_bind_param($stmtO, 'ii', $oid, $customerId);
mysqli_stmt_execute($stmtO);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtO));
mysqli_stmt_close($stmtO);

if (!$order) {
    setError('Order not found.');
    header('Location: ' . BASE_URL . '/customer/orders.php');
    exit;
}

// -------------------------------------------------------
// Business rule: must be >= 2 days before delivery date
// -------------------------------------------------------
$today        = new DateTimeImmutable('today');
$deliveryDate = new DateTimeImmutable($order['odeliverydate']);
$daysToDelivery = (int)$today->diff($deliveryDate)->format('%r%a');

if ($daysToDelivery < 2) {
    setError("Cannot delete Order #$oid: deletion is only allowed at least 2 days before the delivery date (delivery: "
             . $deliveryDate->format('Y-m-d') . ", today: " . $today->format('Y-m-d') . ").");
    header('Location: ' . BASE_URL . '/customer/orders.php');
    exit;
}

// -------------------------------------------------------
// Perform delete inside a transaction + restore stock
// -------------------------------------------------------
mysqli_begin_transaction($conn);
try {
    $fid  = (int)$order['fid'];
    $oqty = (int)$order['oqty'];

    // If order was Open or Approved, restore material stock
    if (in_array((int)$order['ostatus'], [1, 2])) {
        restoreMaterials($conn, $fid, $oqty);
    }

    // Delete OrderFurnitures first (FK child)
    $delOf = mysqli_prepare($conn, "DELETE FROM OrderFurnitures WHERE oid = ?");
    mysqli_stmt_bind_param($delOf, 'i', $oid);
    mysqli_stmt_execute($delOf);
    mysqli_stmt_close($delOf);

    // Delete Order
    $delO = mysqli_prepare($conn, "DELETE FROM Orders WHERE oid = ? AND cid = ?");
    mysqli_stmt_bind_param($delO, 'ii', $oid, $customerId);
    mysqli_stmt_execute($delO);
    mysqli_stmt_close($delO);

    mysqli_commit($conn);

    setSuccess("Order #$oid deleted successfully.");
} catch (Throwable $e) {
    mysqli_rollback($conn);
    setError("Failed to delete Order #$oid. Please try again.");
}

header('Location: ' . BASE_URL . '/customer/orders.php');
exit;
