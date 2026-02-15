<?php
include 'db.php';
$result = $conn->query("SELECT id, name, stok_keluar FROM inventory");
echo "<h1>Inventory Dump</h1>";
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Stok Keluar</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['stok_keluar'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>