<?php
session_start();
include "db.php";

header('Content-Type: application/json');

$tag = isset($_GET['tag']) ? trim($_GET['tag']) : 'all';
$reaction = isset($_GET['reaction']) ? trim($_GET['reaction']) : 'all';

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
    echo json_encode([]);
    exit;
}
$userId = intval($_SESSION['user_id']);

$dateCol = columnExists($conn, "memories", "memory_date") ? "memory_date" : "date";
$tagCol = columnExists($conn, "memories", "tag") ? "tag" : "title";
$reactionCol = columnExists($conn, "memories", "reaction") ? "reaction" : null;
$userCol = ensureUserColumn($conn) ? "user_id" : null;

if ($reaction !== 'all' && $reactionCol === null) {
    echo json_encode([]);
    exit;
}

$where = [];
$params = [];
$types = "";

if ($userCol) {
    $where[] = "`$userCol` = ?";
    $params[] = $userId;
    $types .= "i";
}

if ($tag !== 'all' && $tag !== '') {
    $where[] = "`$tagCol` = ?";
    $params[] = $tag;
    $types .= "s";
}

if ($reaction !== 'all' && $reaction !== '') {
    $where[] = "`$reactionCol` = ?";
    $params[] = $reaction;
    $types .= "s";
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$sql = "
    SELECT `$dateCol` AS memory_date,
           MIN(`$tagCol`) AS tag_value" . ($reactionCol ? ", MIN(`$reactionCol`) AS reaction_value" : "") . "
    FROM memories
    $whereSql
    GROUP BY `$dateCol`
    ORDER BY `$dateCol` ASC
";

$stmt = $conn->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$dates = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['memory_date'])) {
        $reactionValue = $reactionCol ? ($row['reaction_value'] ?? "") : "";
        $dates[] = [
            "date" => $row['memory_date'],
            "tag" => $row['tag_value'],
            "reaction" => $reactionValue
        ];
    }
}

echo json_encode($dates);
?>
