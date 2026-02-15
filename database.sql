CREATE DATABASE IF NOT EXISTS projek_baru_db;
USE projek_baru_db;

-- Table for user management
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    permissions TEXT, -- JSON or comma-separated list of permissions
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for item categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for inventory items
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    barcode VARCHAR(100) NOT NULL,
    stok_masuk INT DEFAULT 0,
    stok_keluar INT DEFAULT 0,
    tanggal_masuk DATE,
    category_id INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Initial Dummy Data
INSERT INTO users (username, password, role, permissions) VALUES 
('admin', '12345', 'admin', 'input,output,edit,kategori,users,laporan'),
('santi', '12345', 'admin', 'input,output,edit,kategori,users,laporan'),
('rina', '12345', 'user', 'output');

INSERT INTO categories (name) VALUES ('Sembako'), ('Minuman'), ('Makanan Ringan'), ('Bumbu Dapur');

INSERT INTO inventory (name, barcode, stok_masuk, stok_keluar, category_id) VALUES 
('Beras Premium 5kg', '8993451023', 50, 0, 1),
('Minyak Goreng 2L', '8993451024', 100, 0, 1),
('Gula Pasir 1kg', '8993451025', 75, 0, 1),
('Telur Ayam 1kg', '8993451026', 30, 0, NULL),
('Tepung Terigu 1kg', '8993451027', 40, 0, NULL);
