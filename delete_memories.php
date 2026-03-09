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

$userCol = ensureUserColumn($conn) ? "user_id" : null;
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}
$userId = intval($_SESSION['user_id']);

$dateCol = columnExists($conn, "memories", "memory_date") ? "memory_date" : "date";

if (isset($_GET['id'])) {
    // Delete specific memory by ID
    $id = intval($_GET['id']);
    if ($userCol) {
        $stmt = $conn->prepare("SELECT * FROM memories WHERE id = ? AND `$userCol` = ?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT * FROM memories WHERE id=$id");
    }

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $imagePath = $row['image_path'];

        // Delete the image file if it exists
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Delete the memory record
        if ($userCol) {
            $stmt = $conn->prepare("DELETE FROM memories WHERE id = ? AND `$userCol` = ?");
            $stmt->bind_param("ii", $id, $userId);
        } else {
            $stmt = $conn->prepare("DELETE FROM memories WHERE id=?");
            $stmt->bind_param("i", $id);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Memory not found']);
    }
} elseif (isset($_GET['date'])) {
    // Delete all memories for a date
    $date = $_GET['date'];
    if ($userCol) {
        $stmt = $conn->prepare("SELECT image_path FROM memories WHERE `$dateCol` = ? AND `$userCol` = ?");
        $stmt->bind_param("si", $date, $userId);
    } else {
        $stmt = $conn->prepare("SELECT image_path FROM memories WHERE `$dateCol` = ?");
        $stmt->bind_param("s", $date);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $filesToDelete = [];
    while ($row = $result->fetch_assoc()) {
        $filesToDelete[] = $row['image_path'];
    }
    $stmt->close();

    // Delete files from filesystem
    foreach ($filesToDelete as $filePath) {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Delete from database
    if ($userCol) {
        $stmt = $conn->prepare("DELETE FROM memories WHERE `$dateCol` = ? AND `$userCol` = ?");
        $stmt->bind_param("si", $date, $userId);
    } else {
        $stmt = $conn->prepare("DELETE FROM memories WHERE `$dateCol` = ?");
        $stmt->bind_param("s", $date);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'No date or ID specified']);
}

$conn->close();
?>

