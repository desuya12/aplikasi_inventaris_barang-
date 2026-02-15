<?php
session_start();
if (!isset($_SESSION['user']) || !in_array('kategori', $_SESSION['permissions'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

// --- API ACTIONS ---

// 1. Get Items for a Category (or all items if needed for selection)
if (isset($_GET['action']) && $_GET['action'] == 'get_items') {
    $cat_id = intval($_GET['cat_id']);

    // Get category name
    $cat_res = $conn->query("SELECT name FROM categories WHERE id = $cat_id");
    $cat_row = $cat_res->fetch_assoc();
    $cat_name = $cat_row ? $cat_row['name'] : '';

    // Get all items to list in the modal
    $items = [];
    $sql = "SELECT id, name, barcode, stok_masuk, stok_keluar, category_id FROM inventory ORDER BY name ASC";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'barcode' => $row['barcode'],
            'stock' => $row['stok_masuk'] - $row['stok_keluar'],
            'is_assigned' => ($row['category_id'] == $cat_id)
        ];
    }

    header('Content-Type: application/json');
    echo json_encode(['category_name' => $cat_name, 'items' => $items]);
    exit();
}

// 2. Save Item Assignments to Category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_items') {
    $cat_id = intval($_POST['category_id']);
    $item_ids = isset($_POST['item_ids']) ? $_POST['item_ids'] : []; // Array of selected IDs

    // First, unassign all items from this category
    $conn->query("UPDATE inventory SET category_id = NULL WHERE category_id = $cat_id");

    // Then, assign selected items
    if (!empty($item_ids)) {
        // Sanitize IDs
        $ids = array_map('intval', $item_ids);
        $ids_str = implode(',', $ids);
        $conn->query("UPDATE inventory SET category_id = $cat_id WHERE id IN ($ids_str)");
    }

    header("Location: kategori.php");
    exit();
}

// 3. Add New Category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    $name = $_POST['name'];
    $conn->query("INSERT INTO categories (name) VALUES ('$name')");
    header("Location: kategori.php");
    exit();
}

// --- VIEW ---
$cats = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Barang</title>
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
            <a href="kategori.php" class="sidebar-btn active"
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
            <h1 class="page-title">Kategori Barang</h1>
            <div class="top-actions">
                <a href="index.php" class="action-btn" id="link-daftar">Daftar Barang</a>
                <?php if (in_array('input', $_SESSION['permissions'])): ?>
                    <a href="input.php" class="action-btn" id="link-input">+ Input Barang</a>
                <?php endif; ?>
                <?php if (in_array('output', $_SESSION['permissions'])): ?>
                    <a href="output.php" class="action-btn" id="link-output">Output</a>
                <?php endif; ?>
                <?php if (in_array('edit', $_SESSION['permissions'])): ?>
                    <a href="edit.php" class="action-btn" id="link-edit">Edit</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Data Content -->
        <div class="content-area">

            <!-- Search Bar -->
            <div class="search-bar-container">
                <input type="text" id="categorySearchInput" class="search-input" placeholder="Cari kategori...">
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="60" style="text-align: center;">PILIH</th>
                            <th style="text-align: center;">ID</th>
                            <th>Nama Kategori</th>
                            <th style="text-align: center;">TGL BUAT</th>
                            <th width="200" style="text-align: center;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody id="categoryTableBody">
                        <?php while ($cat = $cats->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="radio" name="cat_filter" value="<?php echo $cat['id']; ?>"
                                        class="cat-radio-filter" style="width: 18px; height: 18px; cursor: pointer;">
                                </td>
                                <td style="text-align: center; color: #6b7280;">#<?php echo $cat['id']; ?></td>
                                <td style="font-weight: 500;"><?php echo $cat['name']; ?></td>
                                <td style="text-align: center; white-space: nowrap;"><?php echo $cat['created_at']; ?></td>
                                <td style="text-align: center;">
                                    <button onclick="openEditItemsModal(<?php echo $cat['id']; ?>)" class="btn-edit-row"
                                        style="padding: 6px 12px; margin-right: 5px; cursor: pointer; border: none;">EDIT</button>
                                    <button onclick="openViewItemsModal(<?php echo $cat['id']; ?>)" class="btn-edit-row"
                                        style="padding: 6px 12px; background: #3b82f6; cursor: pointer; border: none;">LIHAT</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Button -->
            <div class="bottom-action">
                <button class="btn-wide">TAMBAH KATEGORI BARU</button>
            </div>
        </div>

    </main>

    <!-- Add Category Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <div class="modal-header-pill">MASUKAN KATEGORI BARU</div>
            <form action="kategori.php" method="POST">
                <div class="modal-input-group">
                    <label class="modal-label">NAMA KATEGORI</label>
                    <input type="text" name="name" class="modal-input" placeholder="Contoh: Alat Tulis" required>
                </div>
                <button type="submit" class="btn-modal-submit">SIMPAN KATEGORI</button>
            </form>
        </div>
    </div>

    <!-- Edit Category Items Modal (Checklist) -->
    <div class="modal-overlay" id="editItemsModal">
        <div class="modal-content" style="max-width: 95%; width: 900px; max-height: 85vh; overflow-y: auto;">
            <div class="modal-header-pill" id="editItemsHeader">PILIH BARANG</div>
            <form action="kategori.php" method="POST">
                <input type="hidden" name="action" value="save_items">
                <input type="hidden" name="category_id" id="editCatIdInput">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="60" style="text-align: center;">PILIH</th>
                                <th width="60" style="text-align: center;">ID</th>
                                <th>Nama Barang</th>
                                <th style="text-align: center;">Barcode</th>
                            </tr>
                        </thead>
                        <tbody id="editItemsListBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 24px; text-align: right;">
                    <button type="submit" class="btn-modal-submit" style="width: auto; padding: 12px 24px;">SIMPAN
                        PERUBAHAN</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Category Items Modal -->
    <div class="modal-overlay" id="viewItemsModal">
        <div class="modal-content" style="max-width: 95%; width: 1000px; max-height: 85vh; overflow-y: auto;">
            <div class="modal-header-pill" id="viewItemsHeader">DAFTAR BARANG</div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="60" style="text-align: center;">ID</th>
                            <th>Nama Barang</th>
                            <th style="text-align: center;">Barcode</th>
                            <th style="text-align: center;">Stok</th>
                        </tr>
                    </thead>
                    <tbody id="viewItemsListBody">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 24px; text-align: right;">
                <button onclick="closeModals()" class="action-btn" style="background: #6b7280;">TUTUP</button>
            </div>
        </div>
    </div>

    <script>
        // Search Functionality
        const searchInput = document.getElementById('categorySearchInput');
        const tableBody = document.getElementById('categoryTableBody');

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const categoryCell = rows[i].getElementsByTagName('td')[2];
                if (categoryCell) {
                    const textValue = categoryCell.textContent || categoryCell.innerText;
                    if (textValue.toLowerCase().indexOf(query) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }
        });

        // Modals
        const addModal = document.getElementById('addModal');
        const editItemsModal = document.getElementById('editItemsModal');
        const viewItemsModal = document.getElementById('viewItemsModal');
        const openAddBtn = document.querySelector('.btn-wide');

        // Open Add Modal
        openAddBtn.addEventListener('click', () => {
            addModal.style.display = 'flex';
        });

        // Function to close all modals
        function closeModals() {
            addModal.style.display = 'none';
            editItemsModal.style.display = 'none';
            viewItemsModal.style.display = 'none';
        }

        // Open Edit Items Modal (Checklist)
        async function openEditItemsModal(catId) {
            const response = await fetch(`kategori.php?action=get_items&cat_id=${catId}`);
            const data = await response.json();

            document.getElementById('editItemsHeader').innerText = `PILIH BARANG UNTUK: ${data.category_name.toUpperCase()}`;
            document.getElementById('editCatIdInput').value = catId;

            const tbody = document.getElementById('editItemsListBody');
            tbody.innerHTML = '';

            data.items.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="text-align: center;">
                        <input type="checkbox" name="item_ids[]" value="${item.id}" ${item.is_assigned ? 'checked' : ''} style="width: 18px; height: 18px; cursor: pointer;">
                    </td>
                    <td style="text-align: center; color: #6b7280;">#${item.id}</td>
                    <td style="font-weight: 500;">${item.name}</td>
                    <td style="text-align: center; font-family: monospace;">${item.barcode}</td>
                `;
                tbody.appendChild(tr);
            });

            editItemsModal.style.display = 'flex';
        }

        // Open View Items Modal
        async function openViewItemsModal(catId) {
            const response = await fetch(`kategori.php?action=get_items&cat_id=${catId}`);
            const data = await response.json();

            document.getElementById('viewItemsHeader').innerText = `DAFTAR BARANG: ${data.category_name.toUpperCase()}`;

            const tbody = document.getElementById('viewItemsListBody');
            tbody.innerHTML = '';

            const assignedItems = data.items.filter(i => i.is_assigned);

            if (assignedItems.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #6b7280;">Tidak ada barang dalam kategori ini.</td></tr>';
            } else {
                assignedItems.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="text-align: center; color: #6b7280;">#${item.id}</td>
                        <td style="font-weight: 500;">${item.name}</td>
                        <td style="text-align: center;"><svg class="barcode-pop" jsbarcode-format="CODE128" jsbarcode-value="${item.barcode}" jsbarcode-width="1.2" jsbarcode-height="25" jsbarcode-fontsize="10"></svg></td>
                        <td style="text-align: center; font-weight: 600;">${item.stock}</td>
                    `;
                    tbody.appendChild(tr);
                });
                JsBarcode(".barcode-pop").init();
            }

            viewItemsModal.style.display = 'flex';
        }

        // Close when clicking outside content
        window.addEventListener('click', (e) => {
            if (e.target === addModal || e.target === editItemsModal || e.target === viewItemsModal) {
                closeModals();
            }
        });

        // Category Selection Filter Logic
        const catRadios = document.querySelectorAll('.cat-radio-filter');
        const linkDaftar = document.getElementById('link-daftar');
        const linkInput = document.getElementById('link-input');
        const linkOutput = document.getElementById('link-output');
        const linkEdit = document.getElementById('link-edit');

        // Store original links
        const originalDaftar = linkDaftar ? linkDaftar.href : null;
        const originalInput = linkInput ? linkInput.href : null;
        const originalOutput = linkOutput ? linkOutput.href : null;
        const originalEdit = linkEdit ? linkEdit.href : null;

        catRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                const catId = e.target.value;
                if (e.target.checked) {
                    if (linkDaftar) linkDaftar.href = `${originalDaftar}?cat_id=${catId}`;
                    if (linkInput) linkInput.href = `${originalInput}?cat_id=${catId}`;
                    if (linkOutput) linkOutput.href = `${originalOutput}?cat_id=${catId}`;
                    if (linkEdit) linkEdit.href = `${originalEdit}?cat_id=${catId}`;
                }
            });
        });
    </script>

</body>

</html>