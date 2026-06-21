<?php
/**
 * Helper functions shared across the application.
 */

/**
 * Get a human-readable label for an order status integer.
 */
function orderStatusLabel(int $status): string {
    $labels = [1 => 'Open', 2 => 'Approved', 3 => 'Rejected'];
    return $labels[$status] ?? 'Unknown';
}

/**
 * Get a Bootstrap badge class for an order status.
 */
function orderStatusBadge(int $status): string {
    $classes = [1 => 'bg-primary', 2 => 'bg-success', 3 => 'bg-danger'];
    return $classes[$status] ?? 'bg-secondary';
}

/**
 * Return a safe URL for a furniture image, falling back to placeholder.
 */
function furnitureImageUrl(string $baseUrl, ?string $fimage): string {
    if ($fimage && file_exists(__DIR__ . '/../assets/images/furniture/' . $fimage)) {
        return $baseUrl . '/assets/images/furniture/' . rawurlencode($fimage);
    }
    return $baseUrl . '/assets/images/furniture/placeholder.png';
}

/**
 * Format a DECIMAL/float as HK$ currency.
 */
function money(float $amount): string {
    return 'HK$' . number_format($amount, 2);
}

/**
 * Sanitise a value for safe HTML output.
 */
function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Check whether placing/modifying an order is allowed by verifying that
 * every required material has enough available quantity.
 *
 * @param mysqli $conn
 * @param int    $fid      Furniture ID
 * @param int    $qty      Order quantity (positive integer)
 * @param int    $existingQty  Quantity already consumed by this order (for updates)
 * @return array  ['ok' => bool, 'errors' => string[]]
 */
function checkMaterialStock(mysqli $conn, int $fid, int $qty, int $existingQty = 0): array {
    $errors = [];
    $stmt = mysqli_prepare($conn,
        "SELECT m.mname, m.mavlqty, fm.pmqty
         FROM FurnitureMaterials fm
         JOIN Materials m ON fm.mid = m.mid
         WHERE fm.fid = ?");
    mysqli_bind_param($stmt, 'i', $fid);
    mysqli_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $needed   = $row['pmqty'] * $qty;
        $restored = $row['pmqty'] * $existingQty;   // already consumed by this order
        $available = $row['mavlqty'] + $restored;
        if ($available < $needed) {
            $errors[] = "Insufficient stock for <strong>" . h($row['mname']) . "</strong>: "
                      . "need {$needed} {$row['pmqty']}, available {$available}.";
        }
    }
    mysqli_stmt_close($stmt);
    return ['ok' => empty($errors), 'errors' => $errors];
}

/**
 * Deduct material available quantities for an order.
 * Must be called inside a transaction.
 */
function deductMaterials(mysqli $conn, int $fid, int $qty): void {
    $stmt = mysqli_prepare($conn,
        "UPDATE Materials m
         JOIN FurnitureMaterials fm ON m.mid = fm.mid
         SET m.mavlqty = m.mavlqty - (fm.pmqty * ?)
         WHERE fm.fid = ?");
    mysqli_bind_param($stmt, 'ii', $qty, $fid);
    mysqli_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Restore material available quantities (e.g. on order delete/rejection).
 * Must be called inside a transaction.
 */
function restoreMaterials(mysqli $conn, int $fid, int $qty): void {
    $stmt = mysqli_prepare($conn,
        "UPDATE Materials m
         JOIN FurnitureMaterials fm ON m.mid = fm.mid
         SET m.mavlqty = m.mavlqty + (fm.pmqty * ?)
         WHERE fm.fid = ?");
    mysqli_bind_param($stmt, 'ii', $qty, $fid);
    mysqli_execute($stmt);
    mysqli_stmt_close($stmt);
}
