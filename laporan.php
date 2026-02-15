<?php
session_start();
if (!isset($_SESSION['user']) || !in_array('laporan', $_SESSION['permissions'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Fetch data from transactions table (History Laporan)
$result = $conn->query("SELECT * FROM transactions ORDER BY transaction_date DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Riwayat Transaksi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="static/dashboard.css?v=9">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>

<body>
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
            <a href="laporan.php" class="sidebar-btn active"
                style="text-decoration: none; color: inherit; display: block;">Laporan</a>
        <?php endif; ?>
        <a href="logout.php" class="sidebar-btn exit"><span>Log Out</span></a>
    </nav>

    <main class="main-content">
        <div class="top-header">
            <h1 class="page-title">Laporan Riwayat Transaksi</h1>
        </div>

        <div class="content-area">
            <div class="search-bar-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Cari riwayat...">
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">ID</th>
                            <th>Nama Barang</th>
                            <th>Nama Penginput</th>
                            <th>Tanggal Update</th>
                            <th style="text-align: center;">Stok Masuk</th>
                            <th style="text-align: center;">Stok Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center; color: #6b7280;">#
                                    <?php echo $item['id']; ?>
                                </td>
                                <td style="font-weight: 500;">
                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                </td>
                                <td style="font-weight: 500; color: #4b5563;">
                                    <?php echo htmlspecialchars($item['user_name']); ?>
                                </td>
                                <td style="font-size: 0.9em; color: #6b7280;">
                                    <?php echo date('d M Y H:i', strtotime($item['transaction_date'])); ?>
                                </td>
                                <td style="text-align: center; font-weight: 600; color: #10b981;">
                                    <?php echo $item['stok_masuk']; ?>
                                </td>
                                <td style="text-align: center; font-weight: 600; color: #ef4444;">
                                    <?php echo $item['stok_keluar']; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('searchInput').addEventListener('input', function () {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach(row => {
                let nameCell = row.cells[1]; // Nama Barang
                let userCell = row.cells[2]; // Nama Penginput
                if (nameCell || userCell) {
                    let nameText = nameCell ? (nameCell.textContent || nameCell.innerText) : "";
                    let userText = userCell ? (userCell.textContent || userCell.innerText) : "";
                    let combinedText = nameText + " " + userText;

                    row.style.display = combinedText.toLowerCase().indexOf(filter) > -1 ? "" : "none";
                }
            });
        });
    </script>
</body>

</html>