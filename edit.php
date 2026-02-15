<?php
session_start();
if (!isset($_SESSION['user']) || !in_array('edit', $_SESSION['permissions'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $barcode = $_POST['barcode'];
    $stok = intval($_POST['stock']);
    $stok_keluar = intval($_POST['stock_out']); // New Input
    $currentUser = $_SESSION['user'];

    // 1. Fetch current data to calculate deltas
    $oldQuery = $conn->query("SELECT * FROM inventory WHERE id = $id");
    $oldData = $oldQuery->fetch_assoc();
    $old_stok_masuk = $oldData['stok_masuk'];
    $old_stok_keluar = $oldData['stok_keluar'];

    // 2. Update Inventory
    $stmt = $conn->prepare("UPDATE inventory SET name=?, barcode=?, stok_masuk=?, stok_keluar=?, last_updated_by=? WHERE id=?");
    $stmt->bind_param("ssiisi", $name, $barcode, $stok, $stok_keluar, $currentUser, $id);
    $stmt->execute();

    // 3. Log Transaction (calculate difference)
    $delta_masuk = $stok - $old_stok_masuk;
    $delta_keluar = $stok_keluar - $old_stok_keluar;

    // Only log if there's a change
    if ($delta_masuk != 0 || $delta_keluar != 0) {
        $t_stmt = $conn->prepare("INSERT INTO transactions (inventory_id, item_name, user_name, stok_masuk, stok_keluar) VALUES (?, ?, ?, ?, ?)");
        $t_stmt->bind_param("issii", $id, $name, $currentUser, $delta_masuk, $delta_keluar);
        $t_stmt->execute();
    }

    header("Location: edit.php");
    exit();
}

$result = $conn->query("SELECT * FROM inventory ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="static/dashboard.css?v=11">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>

<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-title">MANAGEMENT</div>
        <a href="index.php" class="sidebar-btn" style="text-decoration: none; color: inherit; display: block;">Setok
            Barang</a>
        <?php if (in_array('kategori', $_SESSION['permissions'])): ?>
            <a href="kategori.php" class="sidebar-btn"
                style="text-decoration: none; color: inherit; display: block;">Kategori</a>
        <?php endif; ?>
        <?php if (in_array('users', $_SESSION['permissions']) || $_SESSION['role'] == 'admin'): ?>
            <a href="users.php" class="sidebar-btn" style="text-decoration: none; color: inherit; display: block;">User
                Management</a>
        <?php endif; ?>
        <?php if (in_array('laporan', $_SESSION['permissions'])): ?>
            <a href="laporan.php" class="sidebar-btn"
                style="text-decoration: none; color: inherit; display: block;">Laporan</a>
        <?php endif; ?>
        <a href="logout.php" class="sidebar-btn exit"><span>Log Out</span></a>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-header">
            <h1 class="page-title">Edit Barang</h1>
            <div class="top-actions">
                <a href="index.php" class="action-btn">Daftar Barang</a>
                <?php if (in_array('input', $_SESSION['permissions'])): ?>
                    <a href="input.php" class="action-btn primary">+ Input Barang</a>
                <?php endif; ?>
                <?php if (in_array('output', $_SESSION['permissions'])): ?>
                    <a href="output.php" class="action-btn">Output</a>
                <?php endif; ?>
                <?php if (in_array('edit', $_SESSION['permissions'])): ?>
                    <a href="edit.php" class="action-btn active">Edit</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-area">
            <div class="search-bar-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Cari barang untuk diedit...">
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">ID</th>
                            <th>Nama Barang</th>
                            <th style="text-align: center;">Barcode</th>
                            <th style="text-align: center;">Stok Masuk</th>
                            <th style="text-align: center;">Stok Keluar</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center; color: #6b7280;">#
                                    <?php echo $item['id']; ?>
                                </td>
                                <td style="font-weight: 500;">
                                    <?php echo $item['name']; ?>
                                </td>
                                <td style="font-family: monospace; text-align: center;">
                                    <svg class="barcode" jsbarcode-format="CODE128"
                                        jsbarcode-value="<?php echo $item['barcode']; ?>" jsbarcode-width="1.5"
                                        jsbarcode-height="30" jsbarcode-fontsize="12">
                                    </svg>
                                </td>
                                <td style="text-align: center; font-weight: 600; color: #10b981;">
                                    <?php echo $item['stok_masuk']; ?>
                                </td>
                                <td style="text-align: center; font-weight: 600; color: #ef4444;">
                                    <?php echo $item['stok_keluar']; ?>
                                </td>
                                </td>
                                <td style="text-align: center;">
                                    <button class="btn-edit-row"
                                        onclick="openEditModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', '<?php echo $item['barcode']; ?>', <?php echo $item['stok_masuk']; ?>, <?php echo $item['stok_keluar']; ?>)">
                                        EDIT
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="modal-header-pill">UBAH DATA BARANG</div>
            <form action="edit.php" method="POST">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-input-group">
                    <label class="modal-label">NAMA BARANG</label>
                    <input type="text" name="name" id="edit-name" class="modal-input" required>
                </div>

                <div class="modal-input-group">
                    <label class="modal-label">BARCODE</label>
                    <input type="text" name="barcode" id="edit-barcode" class="modal-input" required>
                </div>

                <div class="modal-input-group">
                    <label class="modal-label">STOK MASUK</label>
                    <input type="number" name="stock" id="edit-stock" class="modal-input" required>
                </div>

                <div class="modal-input-group">
                    <label class="modal-label">STOK KELUAR</label>
                    <input type="number" name="stock_out" id="edit-stock-out" class="modal-input" required>
                </div>

                <div class="scan-box-area">
                    <div style="margin-bottom: 8px; min-height: 50px;">
                        <svg id="edit-barcode-preview"></svg>
                    </div>
                    <div style="font-size: 12px; font-weight: 600;">PREVIEW BARCODE</div>
                </div>

                <button type="submit" class="btn-modal-submit">UPDATE DATA</button>
            </form>
        </div>
    </div>

    <script>
        JsBarcode(".barcode").init();

        const editModal = document.getElementById('editModal');
        const editBarcodePreview = document.getElementById('edit-barcode-preview');
        const editBarcodeInput = document.getElementById('edit-barcode');

        // Real-time Barcode Preview Logic for Edit
        editBarcodeInput.addEventListener('input', function () {
            const value = this.value;
            if (value.trim() !== "") {
                try {
                    JsBarcode(editBarcodePreview, value, {
                        format: "CODE128",
                        width: 1.5,
                        height: 30,
                        fontSize: 12,
                        displayValue: true
                    });
                    editBarcodePreview.parentElement.style.display = "block";
                } catch (e) {
                    editBarcodePreview.innerHTML = "";
                }
            } else {
                editBarcodePreview.innerHTML = "";
            }
        });

        // Search Functionality
        document.getElementById('searchInput').addEventListener('input', function () {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach(row => {
                let nameCell = row.cells[1];
                if (nameCell) {
                    let textValue = nameCell.textContent || nameCell.innerText;
                    row.style.display = textValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
                }
            });
        });

        // Modal Functionality
        function openEditModal(id, name, barcode, stock, stockOut) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-barcode').value = barcode;
            document.getElementById('edit-stock').value = stock;
            document.getElementById('edit-stock-out').value = stockOut;

            // Generate initial preview
            try {
                JsBarcode(editBarcodePreview, barcode, {
                    format: "CODE128",
                    width: 1.5,
                    height: 30,
                    fontSize: 12,
                    displayValue: true
                });
            } catch (e) {
                editBarcodePreview.innerHTML = "";
            }

            editModal.style.display = 'flex';
        }

        // Close Modal on Outside Click
        window.onclick = function (e) {
            if (e.target.className == 'modal-overlay') {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>

</html>