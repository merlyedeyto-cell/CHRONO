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

if (!isset($_POST['ids']) || !is_array($_POST['ids'])) {
    echo json_encode(['success' => false, 'error' => 'No ids provided']);
    exit;
}

$ids = array_filter(array_map('intval', $_POST['ids']), function($v){ return $v > 0; });
if (empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'Invalid ids']);
    exit;
}

$audioCol = columnExists($conn, "memories", "audio_path") ? "audio_path" : null;
$videoCol = columnExists($conn, "memories", "video_path") ? "video_path" : null;

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

// Fetch file paths to delete
$sql = "SELECT image_path" . ($audioCol ? ", `$audioCol`" : "") . ($videoCol ? ", `$videoCol`" : "") . " FROM memories WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (!empty($row['image_path']) && file_exists($row['image_path'])) {
        unlink($row['image_path']);
    }
    if ($audioCol && !empty($row[$audioCol]) && file_exists($row[$audioCol])) {
        unlink($row[$audioCol]);
    }
    if ($videoCol && !empty($row[$videoCol]) && file_exists($row[$videoCol])) {
        unlink($row[$videoCol]);
    }
}
$stmt->close();

// Delete records
$del = $conn->prepare("DELETE FROM memories WHERE id IN ($placeholders)");
$del->bind_param($types, ...$ids);
if ($del->execute()) {
    echo json_encode(['success' => true, 'deleted' => $del->affected_rows]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
$del->close();
$conn->close();
?>
