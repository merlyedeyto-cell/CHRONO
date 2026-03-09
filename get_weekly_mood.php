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
    echo json_encode(['labels' => [], 'counts' => [], 'colors' => []]);
    exit;
}
$userId = intval($_SESSION['user_id']);
$userCol = ensureUserColumn($conn) ? "user_id" : null;

$dateCol = columnExists($conn, "memories", "memory_date") ? "memory_date" : "date";
$moodCol = columnExists($conn, "memories", "mood") ? "mood" : null;

if ($moodCol === null) {
    echo json_encode(['labels' => [], 'counts' => [], 'colors' => []]);
    exit;
}

$range = $_GET['range'] ?? 'this_week';
$today = new DateTime();
if ($range === 'last_week') {
    $start = (clone $today)->modify('monday last week')->format('Y-m-d');
    $end = (clone $today)->modify('sunday last week')->format('Y-m-d');
} elseif ($range === 'this_month') {
    $start = (clone $today)->modify('first day of this month')->format('Y-m-d');
    $end = (clone $today)->modify('last day of this month')->format('Y-m-d');
} else {
    $start = (clone $today)->modify('monday this week')->format('Y-m-d');
    $end = (clone $today)->modify('sunday this week')->format('Y-m-d');
}

$where = "WHERE `$dateCol` BETWEEN ? AND ? AND `$moodCol` IS NOT NULL AND `$moodCol` <> ''";
$params = [$start, $end];
$types = "ss";
if ($userCol) {
    $where .= " AND `$userCol` = ?";
    $params[] = $userId;
    $types .= "i";
}

$sql = "SELECT `$moodCol` as mood, COUNT(*) as cnt FROM memories $where GROUP BY `$moodCol` ORDER BY cnt DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$palette = [
    "rgba(255,105,180,0.85)",
    "rgba(255,182,193,0.85)",
    "rgba(255,140,201,0.85)",
    "rgba(255,99,164,0.85)",
    "rgba(255,20,147,0.8)",
    "rgba(199,24,91,0.8)",
    "rgba(178,34,94,0.8)"
];

$labels = [];
$counts = [];
$colors = [];
$i = 0;
while ($row = $res->fetch_assoc()) {
    $labels[] = ucfirst($row['mood']);
    $counts[] = intval($row['cnt']);
    $colors[] = $palette[$i % count($palette)];
    $i++;
}

echo json_encode(['labels' => $labels, 'counts' => $counts, 'colors' => $colors]);

$stmt->close();
$conn->close();
?>
