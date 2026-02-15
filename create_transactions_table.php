<?php
include 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT,
    item_name VARCHAR(255),
    user_name VARCHAR(50),
    stok_masuk INT DEFAULT 0,
    stok_keluar INT DEFAULT 0,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'transactions' created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}
?>