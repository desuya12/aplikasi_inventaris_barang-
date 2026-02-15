<?php
session_start();
// Security Check
if (!isset($_SESSION['user']) || !in_array('input', $_SESSION['permissions'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

// --- ACTIONS ---

// Handle Add Item
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $barcode = $_POST['barcode'];
    $stok = intval($_POST['stock']);
    // Default to today if not provided (modal doesn't have input anymore)
    $tanggal_masuk = date('Y-m-d');
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? intval($_POST['category_id']) : NULL;

    // Use prepared statement to prevent SQL injection
    $sql = "INSERT INTO inventory (name, barcode, stok_masuk, tanggal_masuk, category_id, last_updated_by) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $currentUser = $_SESSION['user'];
    $stmt->bind_param("ssisis", $name, $barcode, $stok, $tanggal_masuk, $category_id, $currentUser);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        $t_stmt = $conn->prepare("INSERT INTO transactions (inventory_id, item_name, user_name, stok_masuk, stok_keluar) VALUES (?, ?, ?, ?, 0)");
        $t_stmt->bind_param("issi", $new_id, $name, $currentUser, $stok);
        $t_stmt->execute();

        header("Location: input.php");
        exit();
    } else {
        $error = "Gagal menambahkan barang: " . $conn->error;
    }
}

// --- DATA FETCHING ---
// 1. Get Categories for Dropdown
$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = [];
while ($cat = $cats->fetch_assoc()) {
    $categories[] = $cat;
}

// 2. Get Inventory Items for Table
// Filtering by category if cat_id is set in URL (Global filter support)
$cat_filter = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : null;
$sql = "SELECT * FROM inventory";
if ($cat_filter) {
    $sql .= " WHERE category_id = $cat_filter";
}
$sql .= " ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Barang</title>
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
            <h1 class="page-title">Input Barang</h1>
            <div class="top-actions">
                <a href="index.php" class="action-btn">Daftar Barang</a>
                <a href="input.php" class="action-btn active">+ Input Barang</a>
                <?php if (in_array('output', $_SESSION['permissions'])): ?>
                    <a href="output.php" class="action-btn">Output</a>
                <?php endif; ?>
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
                            <th style="text-align: center;">TGL MASUK</th>
                            <th style="text-align: center;">STOK MASUK</th>
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
                                <td style="text-align: center; white-space: nowrap;">
                                    <?php echo isset($item['tanggal_masuk']) ? $item['tanggal_masuk'] : '-'; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span style="font-weight: 600; color: #10b981">
                                        <?php echo $item['stok_masuk']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Button -->
            <div class="bottom-action">
                <button class="btn-wide">TAMBAH BARANG BARU</button>
            </div>
        </div>

    </main>

    <!-- Add Item Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <div class="modal-header-pill">MASUKAN DATA BARANG BARU</div>

            <form action="input.php" method="POST">
                <div class="modal-input-group">
                    <label class="modal-label">NAMA BARANG</label>
                    <input type="text" name="name" class="modal-input" required>
                </div>

                <div class="modal-input-group">
                    <label class="modal-label">BARCODE</label>
                    <input type="text" name="barcode" id="add-barcode" class="modal-input" required>
                </div>

                <div class="modal-input-group">
                    <label class="modal-label">JUMLAH BARANG</label>
                    <input type="number" name="stock" class="modal-input" required>
                </div>

                <div class="scan-box-area">
                    <div style="margin-bottom: 8px; min-height: 50px;">
                        <svg id="add-barcode-preview"></svg>
                    </div>
                    <div style="font-size: 12px; font-weight: 600;">PREVIEW BARCODE</div>
                </div>

                <button type="submit" class="btn-modal-submit">INPUT DATA</button>
            </form>
        </div>
    </div>

    <script>
        JsBarcode(".barcode").init();

        // Real-time Barcode Preview Logic
        function setupBarcodePreview(inputId, svgId) {
            const input = document.getElementById(inputId);
            const svg = document.getElementById(svgId);

            input.addEventListener('input', function () {
                const value = this.value;
                if (value.trim() !== "") {
                    try {
                        JsBarcode(svg, value, {
                            format: "CODE128",
                            width: 1.5,
                            height: 30,
                            fontSize: 12,
                            displayValue: true
                        });
                        svg.parentElement.style.display = "block";
                    } catch (e) {
                        svg.innerHTML = "";
                    }
                } else {
                    svg.innerHTML = "";
                }
            });
        }

        setupBarcodePreview('add-barcode', 'add-barcode-preview');

        // Search Functionality
        document.getElementById('searchInput').addEventListener('input', function () {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach(row => {
                let nameCell = row.cells[1]; // Column 2: Nama Barang
                if (nameCell) {
                    let textValue = nameCell.textContent || nameCell.innerText;
                    row.style.display = textValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
                }
            });
        });

        // Modals
        const addModal = document.getElementById('addModal');
        const openAddBtn = document.querySelector('.btn-wide');

        // Open Add Modal
        openAddBtn.addEventListener('click', () => {
            addModal.style.display = 'flex';
            document.getElementById('add-barcode-preview').innerHTML = "";
        });

        // Close when clicking outside content
        window.addEventListener('click', (e) => {
            if (e.target === addModal) {
                addModal.style.display = 'none';
            }
        });
    </script>

</body>

</html>