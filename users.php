<?php
session_start();
// Security Check: Only admin can access
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'db.php';

// --- ACTIONS ---

// 1. Add User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = 'user'; // Default role, admin is manual or special case if needed, or we can add role input
    // Allow creating other admins if needed, but template implies mostly user creation. 
    // Let's assume standard 'user' unless we add a role selector. 
    // Wait, the template doesn't have a role selector for Add, only Username/Pass/Perms.
    // However, the Edit modal has a hidden role input. Let's default to 'user' for now.

    $perms = isset($_POST['permissions']) ? implode(',', $_POST['permissions']) : '';

    $stmt = $conn->prepare("INSERT INTO users (username, password, role, permissions) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $role, $perms);
    $stmt->execute();

    header("Location: users.php");
    exit();
}

// 2. Update User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id']);
    $password = $_POST['password'];
    $perms = isset($_POST['permissions']) ? implode(',', $_POST['permissions']) : '';

    if (!empty($password)) {
        // Update with password
        $stmt = $conn->prepare("UPDATE users SET password = ?, permissions = ? WHERE id = ?");
        $stmt->bind_param("ssi", $password, $perms, $id);
    } else {
        // Update permissions only
        $stmt = $conn->prepare("UPDATE users SET permissions = ? WHERE id = ?");
        $stmt->bind_param("si", $perms, $id);
    }
    $stmt->execute();

    header("Location: users.php");
    exit();
}

// 3. Delete User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = intval($_POST['id']);
    // Prevent deleting self (optional but good practice)
    // $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND username != ?");
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: users.php");
    exit();
}

// --- DATA FETCHING ---
$users = $conn->query("SELECT * FROM users ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="static/dashboard.css?v=11">
    <style>
        .perm-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .btn-update {
            background: #6366f1;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 450px;
            max-width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .close-btn {
            cursor: pointer;
            font-size: 1.5rem;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 5px;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .perm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .perm-group {
            border: 1px solid #e5e7eb;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            position: relative;
        }

        .perm-group-label {
            position: absolute;
            top: -10px;
            left: 10px;
            background: white;
            padding: 0 5px;
            font-size: 11px;
            font-weight: 700;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .perm-row-group {
            display: flex;
            gap: 10px;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            position: relative;
            margin: 5px;
        }

        .perm-row-label {
            position: absolute;
            top: -8px;
            left: 8px;
            background: #f9fafb;
            padding: 0 4px;
            font-size: 9px;
            font-weight: 700;
            color: #4b5563;
        }

        .btn-submit {
            width: 100%;
            background: #6366f1;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
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
            <a href="users.php" class="sidebar-btn active"
                style="text-decoration: none; color: inherit; display: block;">User Management</a>
        <?php endif; ?>
        <?php if (in_array('laporan', $_SESSION['permissions'])): ?>
            <a href="laporan.php" class="sidebar-btn"
                style="text-decoration: none; color: inherit; display: block;">Laporan</a>
        <?php endif; ?>

        <a href="logout.php" class="sidebar-btn exit"><span>Log Out</span></a>
    </nav>

    <main class="main-content">
        <div class="top-header">
            <h1 class="page-title">Kelola Hak Akses Pengguna</h1>
            <div class="top-actions">
                <button class="action-btn primary" id="openAddUserModal">+ Tambah Pengguna</button>
            </div>
        </div>

        <div class="content-area">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th style="text-align: center;">Akses Konten</th>
                            <th style="text-align: center;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $users->fetch_assoc()):
                            $u_perms = explode(',', $u['permissions']);
                            ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo ucfirst($u['username']); ?></td>
                                <td>
                                    <span class="status-pill <?php echo ($u['role'] == 'admin') ? 'active' : 'warning'; ?>">
                                        <?php echo strtoupper($u['role']); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 10px; justify-content: center; align-items: center;">

                                        <!-- Stok Barang Group -->
                                        <div class="perm-row-group">
                                            <span class="perm-row-label">Halaman Stok Barang</span>
                                            <?php foreach (['input', 'output', 'edit'] as $p): ?>
                                                <label
                                                    style="display: flex; align-items: center; gap: 4px; font-size: 12px; cursor: default;">
                                                    <input type="checkbox" <?php if (in_array($p, $u_perms))
                                                        echo 'checked'; ?>
                                                        class="perm-checkbox"
                                                        style="cursor: default; pointer-events: none; width: 14px; height: 14px;"
                                                        onclick="return false;">
                                                    <?php echo ucfirst($p); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <!-- Akses Halaman Group -->
                                        <div class="perm-row-group">
                                            <span class="perm-row-label">Akses Halaman</span>
                                            <?php foreach (['kategori', 'users', 'laporan'] as $p): ?>
                                                <label
                                                    style="display: flex; align-items: center; gap: 4px; font-size: 12px; cursor: default;">
                                                    <input type="checkbox" <?php if (in_array($p, $u_perms))
                                                        echo 'checked'; ?>
                                                        class="perm-checkbox"
                                                        style="cursor: default; pointer-events: none; width: 14px; height: 14px;"
                                                        onclick="return false;">
                                                    <?php echo ($p == 'users') ? 'User Management' : ucfirst($p); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <button type="button" class="btn-update btn-edit-user"
                                            data-id="<?php echo $u['id']; ?>" data-username="<?php echo $u['username']; ?>"
                                            data-role="<?php echo $u['role']; ?>"
                                            data-permissions='<?php echo json_encode($u_perms); ?>'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>

                                        <form action="users.php" method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna <?php echo $u['username']; ?>?');"
                                            style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn-update" style="background: #ef4444;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Tambah Pengguna -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Pengguna Baru</h3>
                <span class="close-btn" id="closeAddModal">&times;</span>
            </div>
            <form action="users.php" method="POST" id="addUserForm">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" placeholder="Masukkan username" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="newPassword" class="form-input" placeholder="********"
                        required>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" id="confirmPassword" class="form-input" placeholder="********" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Akses Konten</label>
                    <div class="perm-group">
                        <span class="perm-group-label">Halaman Stok Barang</span>
                        <div class="perm-grid">
                            <?php foreach (['input', 'output', 'edit'] as $p): ?>
                                <label
                                    style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
                                    <input type="checkbox" name="permissions[]" value="<?php echo $p; ?>"
                                        class="new-perm-checkbox">
                                    <?php echo ucfirst($p); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="perm-group">
                        <span class="perm-group-label">Akses Halaman</span>
                        <div class="perm-grid">
                            <?php foreach (['kategori', 'users', 'laporan'] as $p): ?>
                                <label
                                    style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
                                    <input type="checkbox" name="permissions[]" value="<?php echo $p; ?>"
                                        class="new-perm-checkbox">
                                    <?php echo ($p == 'users') ? 'User Management' : ucfirst($p); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Tambah Pengguna</button>
            </form>
        </div>
    </div>

    <!-- Modal Edit Pengguna -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Hak Akses Pengguna</h3>
                <span class="close-btn" id="closeEditModal">&times;</span>
            </div>
            <form action="users.php" method="POST" id="editUserForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editUserId">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" id="displayUsername" class="form-input"
                        style="background-color: #f3f4f6; cursor: not-allowed;" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" id="editPassword" class="form-input" placeholder="********">
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" id="confirmEditPassword" class="form-input" placeholder="********">
                </div>
                <div class="form-group">
                    <label class="form-label">Akses Konten</label>
                    <div class="perm-group">
                        <span class="perm-group-label">Halaman Stok Barang</span>
                        <div class="perm-grid">
                            <?php foreach (['input', 'output', 'edit'] as $p): ?>
                                <label
                                    style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
                                    <input type="checkbox" name="permissions[]" value="<?php echo $p; ?>"
                                        class="edit-perm-checkbox" id="edit-perm-<?php echo $p; ?>">
                                    <?php echo ucfirst($p); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="perm-group">
                        <span class="perm-group-label">Akses Halaman</span>
                        <div class="perm-grid">
                            <?php foreach (['kategori', 'users', 'laporan'] as $p): ?>
                                <label
                                    style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
                                    <input type="checkbox" name="permissions[]" value="<?php echo $p; ?>"
                                        class="edit-perm-checkbox" id="edit-perm-<?php echo $p; ?>">
                                    <?php echo ($p == 'users') ? 'User Management' : ucfirst($p); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="editUserRole">
                <button type="submit" class="btn-submit" style="background: #6366f1;">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        // Modal logic common
        function setupModal(modalId, openBtnId, closeBtnId) {
            const modal = document.getElementById(modalId);
            const openBtn = document.getElementById(openBtnId);
            const closeBtn = document.getElementById(closeBtnId);

            if (openBtn) {
                openBtn.onclick = () => modal.style.display = 'flex';
            }

            if (closeBtn) {
                closeBtn.onclick = () => modal.style.display = 'none';
            }

            window.addEventListener('click', (e) => {
                if (e.target == modal) modal.style.display = 'none';
            });
        }

        // Setup Add Modal
        setupModal('addUserModal', 'openAddUserModal', 'closeAddModal');

        // Setup Edit Modal Logic
        const editModal = document.getElementById('editUserModal');
        const closeEditBtn = document.getElementById('closeEditModal');

        document.querySelectorAll('.btn-edit-user').forEach(btn => {
            btn.onclick = () => {
                const id = btn.dataset.id;
                const username = btn.dataset.username;
                const role = btn.dataset.role;
                const perms = JSON.parse(btn.dataset.permissions);

                document.getElementById('editUserId').value = id;
                document.getElementById('displayUsername').value = username.charAt(0).toUpperCase() + username.slice(1);
                document.getElementById('editUserRole').value = role;

                // Clear password fields on open
                document.getElementById('editPassword').value = '';
                document.getElementById('confirmEditPassword').value = '';

                // Reset and set checkboxes
                document.querySelectorAll('.edit-perm-checkbox').forEach(cb => {
                    cb.checked = perms.includes(cb.value);
                });

                editModal.style.display = 'flex';
            };
        });

        if (closeEditBtn) {
            closeEditBtn.onclick = () => editModal.style.display = 'none';
        }

        window.addEventListener('click', (e) => {
            if (e.target == editModal) editModal.style.display = 'none';
        });

        // Form Validations
        const addForm = document.getElementById('addUserForm');
        if (addForm) {
            addForm.onsubmit = (e) => {
                const pass = document.getElementById('newPassword').value;
                const confirm = document.getElementById('confirmPassword').value;
                const checked = addForm.querySelectorAll('.new-perm-checkbox:checked');

                if (pass !== confirm) {
                    alert('Konfirmasi password tidak cocok!');
                    e.preventDefault();
                    return;
                }

                if (checked.length < 1) {
                    alert('Pilih minimal 1 akses konten!');
                    e.preventDefault();
                    return;
                }
            }
        }

        const editForm = document.getElementById('editUserForm');
        if (editForm) {
            editForm.onsubmit = (e) => {
                const pass = document.getElementById('editPassword').value;
                const confirm = document.getElementById('confirmEditPassword').value;
                const role = document.getElementById('editUserRole').value;
                const checked = editForm.querySelectorAll('.edit-perm-checkbox:checked');

                if (pass !== confirm) {
                    alert('Konfirmasi password baru tidak cocok!');
                    e.preventDefault();
                    return;
                }

                if (role !== 'admin' && checked.length < 1) {
                    alert('Minimal harus pilih 1 akses!');
                    e.preventDefault();
                    return;
                }
            }
        }
    </script>
</body>

</html>