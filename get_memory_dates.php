<?php
include "db.php";

header('Content-Type: application/json');

function columnExists($conn, $table, $column) {
    $sql = "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $exists = $row && intval($row['cnt']) > 0;
    $stmt->close();
    return $exists;
}

$dateCol = columnExists($conn, "memories", "memory_date") ? "memory_date" : "date";

// Get all unique dates that have memories
$sql = "SELECT DISTINCT `$dateCol` as memory_date FROM memories ORDER BY `$dateCol`";
$result = $conn->query($sql);

$dates = [];
while ($row = $result->fetch_assoc()) {
    $dates[] = $row['memory_date'];
}

echo json_encode($dates);
?>

