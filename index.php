<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : null;

$sql = "SELECT * FROM inventory";
if ($cat_id) {
    $sql .= " WHERE category_id = $cat_id";
}
$sql .= " ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="static/dashboard.css?v=11">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>

<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-title">MANAGEMENT</div>
        <a href="index.php" class="sidebar-btn active"
            style="text-decoration: none; color: inherit; display: block;">Setok Barang</a>
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
            <h1 class="page-title">Daftar Barang</h1>
            <div class="top-actions">
                <a href="index.php" class="action-btn active">Daftar Barang</a>
                <?php if (in_array('input', $_SESSION['permissions'])): ?>
                    <a href="input.php" class="action-btn primary">+ Input Barang</a>
                <?php endif; ?>
                <?php if (in_array('output', $_SESSION['permissions'])): ?>
                    <a href="output.php" class="action-btn">Output</a>
                <?php endif; ?>
                <?php if (in_array('edit', $_SESSION['permissions'])): ?>
                    <a href="edit.php" class="action-btn">Edit</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-area">
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
                            <th style="text-align: center;">Stok Masuk</th>
                            <th style="text-align: center;">Stok Keluar</th>
                            <th style="text-align: center;">Stok Terkini</th>
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
                                <td style="text-align: center;">
                                    <span style="font-weight: 600; color: #000000">
                                        <?php echo $item['stok_masuk'] - $item['stok_keluar']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        JsBarcode(".barcode").init();
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
    </script>
</body>

</html>