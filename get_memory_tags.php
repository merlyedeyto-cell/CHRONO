<?php
session_start();
include "db.php";

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Get all memories with their tags for the current month/year
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));

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

// Support both old/new schema names
$dateCol = columnExists($conn, "memories", "memory_date") ? "memory_date" : "date";
$tagCol = columnExists($conn, "memories", "tag") ? "tag" : "title";
$reactionCol = columnExists($conn, "memories", "reaction") ? "reaction" : null;
$userCol = ensureUserColumn($conn) ? "user_id" : null;

// Get the most frequently used tag for each date
$sql = "
    SELECT DAY(`$dateCol`) as day, `$tagCol` as tag_value, COUNT(*) as tag_count
    FROM memories
    WHERE YEAR(`$dateCol`) = ? AND MONTH(`$dateCol`) = ?" . ($userCol ? " AND `$userCol` = ?" : "") . "
    GROUP BY DAY(`$dateCol`), `$tagCol`
    ORDER BY day, tag_count DESC
";

$stmt = $conn->prepare($sql);
if ($userCol) {
    $stmt->bind_param("iii", $year, $month, $userId);
} else {
    $stmt->bind_param("ii", $year, $month);
}
$stmt->execute();
$result = $stmt->get_result();

$memories = [];
$currentDay = null;
$primaryTag = null;

while ($row = $result->fetch_assoc()) {
    $day = $row['day'];
    $tag = $row['tag_value'];

    if ($currentDay !== $day) {
        // New day - store the first (most frequent) tag as the primary tag
        if ($currentDay !== null) {
            $memories[$currentDay] = [$primaryTag];
        }
        $currentDay = $day;
        $primaryTag = $tag;
    }
    // For the same day, we only keep the first (most frequent) tag
}

// Don't forget the last day
if ($currentDay !== null) {
    $memories[$currentDay] = ["tag" => $primaryTag, "reaction" => ""];
}

if ($reactionCol !== null) {
    $sqlReaction = "
        SELECT DAY(`$dateCol`) as day, `$reactionCol` as reaction_value, COUNT(*) as reaction_count
        FROM memories
        WHERE YEAR(`$dateCol`) = ? AND MONTH(`$dateCol`) = ? AND `$reactionCol` <> ''" . ($userCol ? " AND `$userCol` = ?" : "") . "
        GROUP BY DAY(`$dateCol`), `$reactionCol`
        ORDER BY day, reaction_count DESC
    ";
    $stmt2 = $conn->prepare($sqlReaction);
    if ($userCol) {
        $stmt2->bind_param("iii", $year, $month, $userId);
    } else {
        $stmt2->bind_param("ii", $year, $month);
    }
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    $currentDay = null;
    $primaryReaction = null;
    while ($row = $result2->fetch_assoc()) {
        $day = $row['day'];
        $reaction = $row['reaction_value'];
        if ($currentDay !== $day) {
            if ($currentDay !== null) {
                if (!isset($memories[$currentDay])) {
                    $memories[$currentDay] = ["tag" => "", "reaction" => $primaryReaction];
                } else {
                    $memories[$currentDay]["reaction"] = $primaryReaction;
                }
            }
            $currentDay = $day;
            $primaryReaction = $reaction;
        }
    }
    if ($currentDay !== null) {
        if (!isset($memories[$currentDay])) {
            $memories[$currentDay] = ["tag" => "", "reaction" => $primaryReaction];
        } else {
            $memories[$currentDay]["reaction"] = $primaryReaction;
        }
    }
    $stmt2->close();
}

echo json_encode($memories);
?>

