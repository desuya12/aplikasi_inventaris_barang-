<?php
$host = "localhost";
$user = "root";
$pass = "";

// 1. Koneksi ke MySQL (Tanpa pilih DB dulu)
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// 2. Buat Database
$sql_db = "CREATE DATABASE IF NOT EXISTS projek_baru_db";
if ($conn->query($sql_db) === TRUE) {
    echo "Database 'projek_baru_db' berhasil dibuat atau sudah ada.<br>";
} else {
    die("Error buat database: " . $conn->error);
}

// 3. Gunakan Database
$conn->select_db("projek_baru_db");

// 4. SQL untuk tabel dan data dummy
$queries = [
    // 4.1. Hapus tabel lama agar schema baru bisa diterapkan
    "DROP TABLE IF EXISTS transactions",
    "DROP TABLE IF EXISTS inventory",
    "DROP TABLE IF EXISTS categories",
    "DROP TABLE IF EXISTS users",

    // 4.2. Buat Tabel Baru
    "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        permissions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        barcode VARCHAR(100) NOT NULL,
        stok_masuk INT DEFAULT 0,
        stok_keluar INT DEFAULT 0,
        tanggal_masuk DATE,
        category_id INT,
        last_updated_by VARCHAR(50) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )",

    "CREATE TABLE transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inventory_id INT,
        item_name VARCHAR(255),
        user_name VARCHAR(50),
        stok_masuk INT DEFAULT 0,
        stok_keluar INT DEFAULT 0,
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE SET NULL
    )",

    // 4.3. Insert Data Dummy
    // Note: Assuming inventory IDs 1-5 will be created in sequence.
    "INSERT INTO users (username, password, role, permissions) VALUES 
    ('admin', '12345', 'admin', 'input,output,edit,kategori,users,laporan'),
    ('santi', '12345', 'admin', 'input,output,edit,kategori,users,laporan'),
    ('rina', '12345', 'user', 'output')",
    "INSERT INTO categories (name) VALUES ('Sembako'), ('Minuman'), ('Makanan Ringan'), ('Bumbu Dapur')",
    "INSERT INTO inventory (name, barcode, stok_masuk, stok_keluar, tanggal_masuk, category_id, last_updated_by) VALUES 
    ('Beras Premium 5kg', '8993451023', 50, 0, CURDATE(), 1, 'admin'),
    ('Minyak Goreng 2L', '8993451024', 100, 0, CURDATE(), 1, 'admin'),
    ('Gula Pasir 1kg', '8993451025', 75, 0, CURDATE(), 1, 'admin'),
    ('Telur Ayam 1kg', '8993451026', 30, 0, CURDATE(), NULL, 'admin'),
    ('Tepung Terigu 1kg', '8993451027', 40, 0, CURDATE(), NULL, 'admin')",

    // 4.4. LOG TRANSACTIONS (Adjust IDs based on auto_increment)
    // We assume IDs are 1, 2, 3, 4, 5.
    "INSERT INTO transactions (inventory_id, item_name, user_name, stok_masuk, stok_keluar) VALUES 
    (1, 'Beras Premium 5kg', 'admin', 50, 0),
    (2, 'Minyak Goreng 2L', 'admin', 100, 0),
    (3, 'Gula Pasir 1kg', 'admin', 75, 0),
    (4, 'Telur Ayam 1kg', 'admin', 30, 0),
    (5, 'Tepung Terigu 1kg', 'admin', 40, 0)"
];

// Tambahkan query untuk mematikan check foreign key sementara agar bisa truncate/delete
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

foreach ($queries as $q) {
    if ($conn->query($q) === TRUE) {
        // Berhasil
    } else {
        echo "Error saat eksekusi: " . $conn->error . "<br>Query: " . $q . "<br>";
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "<h3>Inisialisasi Selesai!</h3>";
echo "<p>Tabel telah dibuat dan data dummy telah dimasukkan.</p>";
echo "<a href='login.php'>Klik di sini untuk ke halaman Login</a>";

$conn->close();
?>