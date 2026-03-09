<?php
session_start();
include "db.php";

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

function ensureVideoColumn($conn) {
    if (!columnExists($conn, "memories", "video_path")) {
        $conn->query("ALTER TABLE memories ADD COLUMN video_path VARCHAR(500) DEFAULT NULL AFTER audio_path");
    }
    return columnExists($conn, "memories", "video_path");
}

function ensureMoodColumn($conn) {
    if (!columnExists($conn, "memories", "mood")) {
        if (columnExists($conn, "memories", "description")) {
            $conn->query("ALTER TABLE memories ADD COLUMN mood VARCHAR(50) DEFAULT NULL AFTER description");
        } elseif (columnExists($conn, "memories", "message")) {
            $conn->query("ALTER TABLE memories ADD COLUMN mood VARCHAR(50) DEFAULT NULL AFTER message");
        } else {
            $conn->query("ALTER TABLE memories ADD COLUMN mood VARCHAR(50) DEFAULT NULL");
        }
    }
    return columnExists($conn, "memories", "mood");
}

function bindParams($stmt, $types, $values) {
    $refs = [];
    $refs[] = $types;
    foreach ($values as $k => $v) {
        $refs[] = &$values[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$date = $_POST['memory_date'] ?? '';
$message = $_POST['message'] ?? '';
$tag = $_POST['tag'] ?? 'general';
$mood = $_POST['mood'] ?? '';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=not_logged_in");
    exit();
}
$userId = intval($_SESSION['user_id']);

function normalizeTagValue($value) {
    $value = trim($value ?? '');
    $value = preg_replace('/\s+/', ' ', $value);
    return mb_strtolower($value, 'UTF-8');
}

function displayTagLabel($value) {
    $map = [
        'general' => 'General',
        'birthday' => 'Birthday',
        'anniversary' => 'Anniversary',
        'holiday' => 'Holiday',
        'travel' => 'Travel',
        'food' => 'Food',
        'family' => 'Family',
        'friends' => 'Friends',
        'work' => 'Work',
        'special' => 'Special'
    ];
    return $map[$value] ?? $value;
}

$dateCol = columnExists($conn, "memories", "memory_date") ? "memory_date" : "date";
$descCol = columnExists($conn, "memories", "description") ? "description" : "message";
$hasTagCol = columnExists($conn, "memories", "tag");
$hasTitleCol = columnExists($conn, "memories", "title");
$tagCol = $hasTagCol ? "tag" : ($hasTitleCol ? "title" : null);
$videoCol = columnExists($conn, "memories", "video_path") ? "video_path" : null;
$audioCol = columnExists($conn, "memories", "audio_path") ? "audio_path" : null;
$userCol = ensureUserColumn($conn) ? "user_id" : null;
$moodCol = ensureMoodColumn($conn) ? "mood" : null;

$targetDir = "uploads/";
if (!is_dir($targetDir)) {
    mkdir($targetDir);
}

if ($date === '') {
    header("Location: calendar.php?error=invalid_date");
    exit();
}

if (!isset($_FILES["media"])) {
    header("Location: calendar.php?error=no_files");
    exit();
}

if ($_FILES["media"]["error"] !== UPLOAD_ERR_OK) {
    $details = "Upload error code: " . intval($_FILES["media"]["error"]);
    header("Location: calendar.php?error=upload_errors&details=" . urlencode($details));
    exit();
}

// Enforce same tag for all memories on the same date (if tag/title column exists)
if ($tagCol !== null) {
    if ($hasTagCol && $hasTitleCol) {
        $checkSql = "SELECT COALESCE(NULLIF(`tag`, ''), `title`) as tag_value FROM memories WHERE `$dateCol` = ?". ($userCol ? " AND `$userCol` = ?" : "") ." LIMIT 1";
    } else {
        $checkSql = "SELECT `$tagCol` as tag_value FROM memories WHERE `$dateCol` = ?". ($userCol ? " AND `$userCol` = ?" : "") ." LIMIT 1";
    }
    $checkStmt = $conn->prepare($checkSql);
    if ($userCol) {
        $checkStmt->bind_param("si", $date, $userId);
    } else {
        $checkStmt->bind_param("s", $date);
    }
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $existingTag = $checkResult->fetch_assoc()['tag_value'];
        if ($existingTag !== null && trim($existingTag) !== '') {
            $incomingTag = $tagCol === "title" ? displayTagLabel($tag) : $tag;
            // Auto-align tag to the first memory's tag for this date
            if (normalizeTagValue($existingTag) !== normalizeTagValue($incomingTag)) {
                $tag = $existingTag;
            }
        }
    }
    $checkStmt->close();
}

$imageTargetFile = null;
$videoTargetFile = null;
$mediaType = $_FILES["media"]["type"] ?? '';
$mediaName = time() . "_" . basename($_FILES["media"]["name"]);
$mediaTarget = $targetDir . $mediaName;

if (strpos($mediaType, 'video/') === 0 && $videoCol === null) {
    if (ensureVideoColumn($conn)) {
        $videoCol = "video_path";
    } else {
        header("Location: calendar.php?error=upload_errors&details=" . urlencode("Video not supported yet"));
        exit();
    }
}

if (!move_uploaded_file($_FILES["media"]["tmp_name"], $mediaTarget)) {
    header("Location: calendar.php?error=upload_errors&details=" . urlencode("Failed to move uploaded file"));
    exit();
}

if (strpos($mediaType, 'video/') === 0) {
    $videoTargetFile = $mediaTarget;
} else {
    $imageTargetFile = $mediaTarget;
}

// Optional audio upload
$audioTargetFile = null;
if ($audioCol !== null && isset($_FILES["audio"]) && $_FILES["audio"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["audio"]["error"] !== UPLOAD_ERR_OK) {
        $details = "Audio upload error code: " . intval($_FILES["audio"]["error"]);
        header("Location: calendar.php?error=upload_errors&details=" . urlencode($details));
        exit();
    }
    $audioName = time() . "_" . basename($_FILES["audio"]["name"]);
    $audioTargetFile = $targetDir . $audioName;
    if (!move_uploaded_file($_FILES["audio"]["tmp_name"], $audioTargetFile)) {
        header("Location: calendar.php?error=upload_errors&details=" . urlencode("Failed to move uploaded audio file"));
        exit();
    }
}

// Video handled by media input

$columns = [];
$placeholders = [];
$values = [];
$types = "";

$columns[] = $dateCol;
$placeholders[] = "?";
$values[] = $date;
$types .= "s";

if ($userCol !== null) {
    $columns[] = $userCol;
    $placeholders[] = "?";
    $values[] = $userId;
    $types .= "i";
}

if ($descCol !== null) {
    $columns[] = $descCol;
    $placeholders[] = "?";
    $values[] = $message;
    $types .= "s";
}

if ($tagCol !== null) {
    $columns[] = $tagCol;
    $placeholders[] = "?";
    $values[] = $tagCol === "title" ? displayTagLabel($tag) : $tag;
    $types .= "s";
}

if ($moodCol !== null) {
    $columns[] = $moodCol;
    $placeholders[] = "?";
    $values[] = mb_substr(trim($mood), 0, 50, 'UTF-8');
    $types .= "s";
}

if ($imageTargetFile !== null) {
    $columns[] = "image_path";
    $placeholders[] = "?";
    $values[] = $imageTargetFile;
    $types .= "s";
}

if ($audioCol !== null && $audioTargetFile !== null) {
    $columns[] = $audioCol;
    $placeholders[] = "?";
    $values[] = $audioTargetFile;
    $types .= "s";
}

if ($videoCol !== null && $videoTargetFile !== null) {
    $columns[] = $videoCol;
    $placeholders[] = "?";
    $values[] = $videoTargetFile;
    $types .= "s";
}

$sql = "INSERT INTO memories (" . implode(",", $columns) . ") VALUES (" . implode(",", $placeholders) . ")";
$stmt = $conn->prepare($sql);
bindParams($stmt, $types, $values);

if ($stmt->execute()) {
    header("Location: calendar.php?success=true&date=" . urlencode($date));
    exit();
}

if ($conn->errno === 1062) {
    header("Location: calendar.php?error=date_exists");
    exit();
}

header("Location: calendar.php?error=upload_errors&details=" . urlencode($conn->error));
exit();
?>

