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

function ensureReactionColumn($conn) {
    if (!columnExists($conn, "memories", "reaction")) {
        // Add column in a schema-safe way (description or message may not exist)
        if (columnExists($conn, "memories", "description")) {
            $conn->query("ALTER TABLE memories ADD COLUMN reaction VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER description");
        } elseif (columnExists($conn, "memories", "message")) {
            $conn->query("ALTER TABLE memories ADD COLUMN reaction VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER message");
        } else {
            $conn->query("ALTER TABLE memories ADD COLUMN reaction VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL");
        }
    }
    // Ensure column can store emojis
    $conn->query("ALTER TABLE memories MODIFY reaction VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    return columnExists($conn, "memories", "reaction");
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}
$userId = intval($_SESSION['user_id']);
$userCol = ensureUserColumn($conn) ? "user_id" : null;

if (!isset($_POST['id']) || !isset($_POST['reaction'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$id = intval($_POST['id']);
$reaction = $_POST['reaction'];
$reaction = mb_substr($reaction, 0, 16, 'UTF-8');

if (!ensureReactionColumn($conn)) {
    echo json_encode(['success' => false, 'error' => 'Reaction not supported']);
    exit;
}

$isEmpty = trim($reaction) === '';
if ($isEmpty) {
    if ($userCol) {
        $stmt = $conn->prepare("UPDATE memories SET reaction = NULL WHERE id = ? AND `$userCol` = ?");
        $stmt->bind_param("ii", $id, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE memories SET reaction = NULL WHERE id = ?");
        $stmt->bind_param("i", $id);
    }
} else {
    if ($userCol) {
        $stmt = $conn->prepare("UPDATE memories SET reaction = ? WHERE id = ? AND `$userCol` = ?");
        $stmt->bind_param("sii", $reaction, $id, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE memories SET reaction = ? WHERE id = ?");
        $stmt->bind_param("si", $reaction, $id);
    }
}

if ($stmt->execute()) {
    if ($stmt->affected_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Memory not found or no change']);
        $stmt->close();
        $conn->close();
        exit;
    }
    if ($userCol) {
        $check = $conn->prepare("SELECT reaction FROM memories WHERE id = ? AND `$userCol` = ?");
        $check->bind_param("ii", $id, $userId);
    } else {
        $check = $conn->prepare("SELECT reaction FROM memories WHERE id = ?");
        $check->bind_param("i", $id);
    }
    $check->execute();
    $res = $check->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $saved = $row ? $row['reaction'] : null;
    $check->close();
    echo json_encode(['success' => true, 'reaction' => $saved, 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>
