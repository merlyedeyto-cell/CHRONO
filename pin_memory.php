<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$memoryId = isset($_POST['memory_id']) ? intval($_POST['memory_id']) : 0;
if ($memoryId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid memory id']);
    exit;
}

// Check if already pinned
$stmt = $conn->prepare("SELECT id FROM pinned_memories WHERE user_id = ? AND memory_id = ?");
$stmt->bind_param("ii", $userId, $memoryId);
$stmt->execute();
$result = $stmt->get_result();
$exists = $result && $result->num_rows > 0;
$stmt->close();

if ($exists) {
    $stmt = $conn->prepare("DELETE FROM pinned_memories WHERE user_id = ? AND memory_id = ?");
    $stmt->bind_param("ii", $userId, $memoryId);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok, 'pinned' => false]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO pinned_memories (user_id, memory_id) VALUES (?, ?)");
$stmt->bind_param("ii", $userId, $memoryId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok, 'pinned' => true]);
?>
