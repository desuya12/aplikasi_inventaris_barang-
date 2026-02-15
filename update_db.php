<?php
include 'db.php';

$sql = "ALTER TABLE inventory ADD COLUMN IF NOT EXISTS last_updated_by VARCHAR(50) DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Column 'last_updated_by' added successfully or already exists.";
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?>