<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

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

$sql = "
    SELECT m.id, m.`$dateCol` AS memory_date, m.`$tagCol` AS tag_value, m.image_path, m.video_path
    FROM pinned_memories p
    JOIN memories m ON m.id = p.memory_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$pins = [];
while ($row = $result->fetch_assoc()) {
    $pins[] = [
        'id' => $row['id'],
        'date' => $row['memory_date'],
        'tag' => $row['tag_value'],
        'has_image' => !empty($row['image_path']),
        'has_video' => !empty($row['video_path'])
    ];
}

echo json_encode(['success' => true, 'pins' => $pins]);
?>
