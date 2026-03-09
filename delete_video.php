<?php
session_start();
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

function ensureUserColumn($conn) {
    if (!columnExists($conn, "memories", "user_id")) {
        $conn->query("ALTER TABLE memories ADD COLUMN user_id INT NULL");
    }
    return columnExists($conn, "memories", "user_id");
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}
$userId = intval($_SESSION['user_id']);
$userCol = ensureUserColumn($conn) ? "user_id" : null;

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'No memory ID specified']);
    exit;
}

if (!columnExists($conn, "memories", "video_path")) {
    echo json_encode(['success' => false, 'error' => 'Video not supported']);
    exit;
}

$id = intval($_GET['id']);
if ($userCol) {
    $stmt = $conn->prepare("SELECT video_path FROM memories WHERE id = ? AND `$userCol` = ?");
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT video_path FROM memories WHERE id=$id");
}

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Memory not found']);
    exit;
}

$row = $result->fetch_assoc();
$videoPath = $row['video_path'];

if ($videoPath && file_exists($videoPath)) {
    unlink($videoPath);
}

if ($userCol) {
    $sql = "UPDATE memories SET video_path = NULL WHERE id = ? AND `$userCol` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $userId);
} else {
    $sql = "UPDATE memories SET video_path = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>
