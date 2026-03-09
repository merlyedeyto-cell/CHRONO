<?php
include "db.php";

header('Content-Type: application/json');

$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
if ($tag === '' || $tag === 'all') {
    echo json_encode([]);
    exit;
}

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
$tagCol = columnExists($conn, "memories", "tag") ? "tag" : "title";

$sql = "SELECT DISTINCT `$dateCol` AS memory_date
        FROM memories
        WHERE `$tagCol` = ?
        ORDER BY `$dateCol` ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $tag);
$stmt->execute();
$result = $stmt->get_result();

$dates = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['memory_date'])) {
        $dates[] = $row['memory_date'];
    }
}

echo json_encode($dates);
?>
