<?php
session_start();
if (!isset($_SESSION['user']) || !in_array('output', $_SESSION['permissions'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Handle Output (Barang Keluar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $qty = intval($_POST['stock']);

    // Logic: Increase stok_keluar. 
    // Note: Stock remaining calculation (masuk - keluar) happens on display or in separate logic if needed.
    // Here we just record the output.
    $currentUser = $_SESSION['user'];
    $conn->query("UPDATE inventory SET stok_keluar = stok_keluar + $qty, last_updated_by = '$currentUser' WHERE id = $id");

    // Fetch item name for the log
    $itemQuery = $conn->query("SELECT name FROM inventory WHERE id = $id");
    $itemData = $itemQuery->fetch_assoc();
    $itemName = $itemData['name'];

    // Log transaction
    $t_stmt = $conn->prepare("INSERT INTO transactions (inventory_id, item_name, user_name, stok_masuk, stok_keluar) VALUES (?, ?, ?, 0, ?)");
    $t_stmt->bind_param("issi", $id, $itemName, $currentUser, $qty);
    $t_stmt->execute();

    header("Location: output.php");
    exit();
}

$result = $conn->query("SELECT * FROM inventory ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Output Barang</title>
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

        <a href="logout.php" class="sidebar-btn exit">
            <span>Log Out</span>
        </a>
    </nav>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header & Actions -->
        <div class="top-header">
            <h1 class="page-title">Output Barang</h1>
            <div class="top-actions">
                <a href="index.php" class="action-btn">Daftar Barang</a>
                <?php if (in_array('input', $_SESSION['permissions'])): ?>
                    <a href="input.php" class="action-btn">+ Input Barang</a>
                <?php endif; ?>
                <a href="output.php" class="action-btn active">Output</a>
                <?php if (in_array('edit', $_SESSION['permissions'])): ?>
                    <a href="edit.php" class="action-btn">Edit</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Data Content -->
        <div class="content-area">

            <!-- Search Bar -->
            <div class="search-bar-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Cari barang...">
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">ID</th>
                            <th>Nama Barang</th>
                            <th style="text-align: center;">Barcode</th>
                            <th style="text-align: center;">STOK KELUAR</th>
                            <th width="160" style="text-align: center;">BARANG KELUAR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center; color: #6b7280;">#<?php echo $item['id']; ?></td>
                                <td style="font-weight: 500;"><?php echo $item['name']; ?></td>
                                <td style="font-family: monospace; text-align: center;">
                                    <svg class="barcode" jsbarcode-format="CODE128"
                                        jsbarcode-value="<?php echo $item['barcode']; ?>" jsbarcode-width="1.5"
                                        jsbarcode-height="30" jsbarcode-fontsize="12">
                                    </svg>
                                </td>
                                <td style="text-align: center;">
                                    <span style="font-weight: 600; color: #ef4444">
                                        <?php echo $item['stok_keluar']; ?>/<?php echo $item['stok_masuk']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <button class="btn-edit-row"
                                        onclick="openEditModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', '<?php echo $item['barcode']; ?>')">
                                        KELUAR
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </main>

    <!-- Output Item Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="modal-header-pill">BARANG KELUAR</div>

            <form action="output.php" method="POST">
                <input type="hidden" name="id" id="edit-id">

                <div class="modal-input-group">
                    <label class="modal-label">NAMA BARANG</label>
                    <input type="text" name="name" id="edit-name" class="modal-input" readonly>
                </div>

                <div class="modal-input-group">
                    <label class="modal-label">BARCODE</label>
                    <input type="text" name="barcode" id="edit-barcode" class="modal-input" readonly>
                </div>

                <div class="modal-input-group">
                    <label class="modal-label">JUMLAH STOK KELUAR</label>
                    <input type="number" name="stock" id="edit-stock" class="modal-input" value="0" required>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn-modal-submit">KELUAR SEKARANG</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        JsBarcode(".barcode").init();

        const editModal = document.getElementById('editModal');

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

        // Open Modal Function
        function openEditModal(id, name, barcode) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-barcode').value = barcode;
            document.getElementById('edit-stock').value = 0; // Reset to 0
            editModal.style.display = 'flex';
        }

        // Close Modal
        window.onclick = function (e) {
            if (e.target.className == 'modal-overlay') {
                e.target.style.display = 'none';
            }
        }
    </script>

</body>

</html>