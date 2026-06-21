<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireStaff();

$errors  = [];
$success = '';

// -------------------------------------------------------
// Load materials for selection in the form
// -------------------------------------------------------
$matResult = mysqli_query($conn, "SELECT mid, mname, munit FROM Materials ORDER BY mname");

// -------------------------------------------------------
// Handle form submission
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname   = trim($_POST['fname']   ?? '');
    $fdesc   = trim($_POST['fdesc']   ?? '');
    $fprice  = trim($_POST['fprice']  ?? '');
    $mids    = $_POST['mids']   ?? [];   // array of material IDs
    $pmqtys  = $_POST['pmqtys'] ?? [];  // array of quantities (parallel to $mids)

    // Validate basics
    if ($fname  === '') $errors[] = 'Furniture name is required.';
    if ($fdesc  === '') $errors[] = 'Description is required.';
    if (!is_numeric($fprice) || (float)$fprice <= 0) $errors[] = 'Price must be a positive number.';
    if (empty($mids)) $errors[] = 'At least one material must be specified.';

    // Validate image upload
    $fimage = null;
    if (isset($_FILES['fimage']) && $_FILES['fimage']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mimeType = mime_content_type($_FILES['fimage']['tmp_name']);
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Image must be JPEG, PNG, GIF, or WebP.';
        } else {
            $ext    = pathinfo($_FILES['fimage']['name'], PATHINFO_EXTENSION);
            $fimage = 'furniture_' . uniqid() . '.' . $ext;
        }
    } elseif (isset($_FILES['fimage']) && $_FILES['fimage']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Image upload error.';
    }

    // Validate materials
    $materialRows = [];
    foreach ($mids as $idx => $mid) {
        $mid   = (int)$mid;
        $pmqty = (int)($pmqtys[$idx] ?? 0);
        if ($mid <= 0)    { $errors[] = "Invalid material at row " . ($idx+1) . "."; continue; }
        if ($pmqty <= 0)  { $errors[] = "Material quantity at row " . ($idx+1) . " must be > 0."; continue; }
        $materialRows[$mid] = $pmqty;  // deduplicate by mid
    }

    if (empty($errors)) {
        $fprice = (float)$fprice;

        mysqli_begin_transaction($conn);
        try {
            // Insert furniture
            $insF = mysqli_prepare($conn,
                "INSERT INTO Furnitures (fname, fdesc, fimage, fprice) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($insF, 'sssd', $fname, $fdesc, $fimage, $fprice);
            mysqli_stmt_execute($insF);
            $newFid = (int)mysqli_insert_id($conn);
            mysqli_stmt_close($insF);

            // Insert FurnitureMaterials
            $insFm = mysqli_prepare($conn,
                "INSERT INTO FurnitureMaterials (fid, mid, pmqty) VALUES (?, ?, ?)");
            foreach ($materialRows as $mid => $pmqty) {
                mysqli_stmt_bind_param($insFm, 'iii', $newFid, $mid, $pmqty);
                mysqli_stmt_execute($insFm);
            }
            mysqli_stmt_close($insFm);

            // Move uploaded image if present
            if ($fimage && isset($_FILES['fimage']['tmp_name'])) {
                $dest = ROOT_DIR . '/assets/images/furniture/' . $fimage;
                if (!move_uploaded_file($_FILES['fimage']['tmp_name'], $dest)) {
                    throw new RuntimeException('Failed to save image.');
                }
            }

            mysqli_commit($conn);
            $success = "Furniture item #$newFid \"" . h($fname) . "\" added successfully!";

            // Refresh materials dropdown
            $matResult = mysqli_query($conn, "SELECT mid, mname, munit FROM Materials ORDER BY mname");
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $errors[] = 'Failed to save furniture: ' . $e->getMessage();
        }
    }
}

$pageTitle  = 'Insert Furniture';
$navSection = 'staff';
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="page-header"><i class="bi bi-table me-2"></i>Insert Furniture Item</h2>

<div class="row justify-content-center">
<div class="col-lg-8">
    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success alert-auto-dismiss">
        <i class="bi bi-check-circle me-1"></i><?= $success ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post" action="" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">
                        Furniture Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="fname" class="form-control"
                           value="<?= h($_POST['fname'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Price (HK$) <span class="text-danger">*</span>
                    </label>
                    <input type="number" name="fprice" class="form-control"
                           min="0.01" step="0.01"
                           value="<?= h($_POST['fprice'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        Description <span class="text-danger">*</span>
                    </label>
                    <textarea name="fdesc" class="form-control" rows="2"
                              required><?= h($_POST['fdesc'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Furniture Image</label>
                    <input type="file" name="fimage" class="form-control"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">Optional. JPEG, PNG, GIF, or WebP.</div>
                </div>
            </div>

            <hr class="my-4">

            <h6 class="fw-bold mb-3">
                <i class="bi bi-boxes me-1"></i>Materials Required
                <small class="text-muted fw-normal">(at least one)</small>
            </h6>

            <div id="material-rows">
                <div class="row g-2 mb-2 material-row-item align-items-end">
                    <div class="col-6">
                        <label class="form-label small">Material</label>
                        <select name="mids[]" class="form-select" required>
                            <option value="">-- Select Material --</option>
                            <?php while ($m = mysqli_fetch_assoc($matResult)): ?>
                            <option value="<?= $m['mid'] ?>"><?= h($m['mname']) ?> (<?= h($m['munit']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label small">Qty per item</label>
                        <input type="number" name="pmqtys[]" class="form-control"
                               min="1" placeholder="e.g. 2" required>
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-row"
                                title="Remove row">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="button" id="add-material" class="btn btn-sm btn-outline-success mb-4">
                <i class="bi bi-plus-circle me-1"></i>Add Another Material
            </button>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success flex-fill fw-semibold">
                    <i class="bi bi-save me-1"></i>Save Furniture
                </button>
                <a href="<?= BASE_URL ?>/staff/index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>

<script>
// Store the material options HTML so we can clone rows
const matSelectHtml = document.querySelector('select[name="mids[]"]').innerHTML;

document.getElementById('add-material').addEventListener('click', () => {
    const container = document.getElementById('material-rows');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 material-row-item align-items-end';
    row.innerHTML = `
        <div class="col-6">
            <label class="form-label small">Material</label>
            <select name="mids[]" class="form-select" required>
                ${matSelectHtml}
            </select>
        </div>
        <div class="col-4">
            <label class="form-label small">Qty per item</label>
            <input type="number" name="pmqtys[]" class="form-control" min="1" placeholder="e.g. 2" required>
        </div>
        <div class="col-2">
            <button type="button" class="btn btn-outline-danger btn-sm remove-row" title="Remove row">
                <i class="bi bi-trash"></i>
            </button>
        </div>`;
    container.appendChild(row);
});

document.getElementById('material-rows').addEventListener('click', e => {
    if (e.target.closest('.remove-row')) {
        const rows = document.querySelectorAll('.material-row-item');
        if (rows.length > 1) {
            e.target.closest('.material-row-item').remove();
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
