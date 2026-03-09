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

$date = $_GET['date'] ?? '';
$reactionFilter = $_GET['reaction'] ?? '';

$dateCol = columnExists($conn, "memories", "memory_date") ? "memory_date" : "date";
$titleCol = columnExists($conn, "memories", "tag") ? "tag" : "title";
$descCol = columnExists($conn, "memories", "description") ? "description" : "message";
$audioCol = columnExists($conn, "memories", "audio_path") ? "audio_path" : null;
$videoCol = columnExists($conn, "memories", "video_path") ? "video_path" : null;
$reactionCol = columnExists($conn, "memories", "reaction") ? "reaction" : null;
$moodCol = columnExists($conn, "memories", "mood") ? "mood" : null;
$userCol = ensureUserColumn($conn) ? "user_id" : null;

if (!isset($_SESSION['user_id'])) {
    echo "<div style='text-align: center; padding: 40px; color: #ff1493;'>
            <h3>Please login</h3>
          </div>";
    exit;
}
$userId = intval($_SESSION['user_id']);

$where = "WHERE `$dateCol` = ?";
$params = [$date];
$types = "s";
if ($userCol !== null) {
    $where .= " AND `$userCol` = ?";
    $params[] = $userId;
    $types .= "i";
}
if ($reactionFilter !== '' && $reactionCol !== null) {
    $where .= " AND `$reactionCol` = ?";
    $params[] = $reactionFilter;
    $types .= "s";
}
$sql = "SELECT * FROM memories $where ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<div style='text-align: center; padding: 40px; color: #ff1493;'>
            <h3>No memories found</h3>
            <p>Start creating memories for this special date! ðŸŒŸ</p>
          </div>";
    exit;
}

echo "<div class='modal-memories-grid'>";
echo "<div class='modal-left-column'>"; // Pictures column

$memories = [];
while ($row = $result->fetch_assoc()) {
    $memories[] = $row;
}

$pinnedIds = [];
if (isset($_SESSION['user_id']) && count($memories) > 0) {
    $userId = $_SESSION['user_id'];
    $ids = array_map(fn($m) => intval($m['id']), $memories);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $typesPin = str_repeat('i', count($ids) + 1);
    $sqlPins = "SELECT memory_id FROM pinned_memories WHERE user_id = ? AND memory_id IN ($placeholders)";
    $stmtPins = $conn->prepare($sqlPins);
    $stmtPins->bind_param($typesPin, $userId, ...$ids);
    $stmtPins->execute();
    $resPins = $stmtPins->get_result();
    while ($row = $resPins->fetch_assoc()) {
        $pinnedIds[intval($row['memory_id'])] = true;
    }
    $stmtPins->close();
}

// Display all pictures in a scrollable container
echo "<div class='memory-pics-container'>";
foreach ($memories as $index => $memory) {
    $picNumber = $index + 1;
    $imagePath = $memory['image_path'] ?? '';
    $videoPath = $videoCol !== null ? ($memory[$videoCol] ?? '') : '';
    $updatedAt = isset($memory['updated_at']) ? strtotime($memory['updated_at']) : time();
    $imageUrl = $imagePath !== '' ? ($imagePath . '?v=' . $updatedAt) : '';
    if (!empty($imagePath) || !empty($videoPath)) {
        echo "<div class='memory-pic-card' data-index='{$index}'>";
        echo "<div class='pic-number-badge'>#{$picNumber}</div>";
        echo "<div class='memory-media-stack'>";
        if (!empty($imagePath)) {
            echo "<div class='memory-image-wrap'>";
            echo "<img src='" . $imageUrl . "' alt='Memory photo {$picNumber}' class='memory-image' onerror=\"this.style.display='none'; this.closest('.memory-image-wrap').classList.add('img-missing');\">";
            echo "<div class='no-photo-text'>No photo</div>";
            $reactionLabel = isset($memory['reaction']) && $memory['reaction'] !== '' ? $memory['reaction'] : 'React';
            echo "<div class='pic-react-underlay'>";
            echo "<div class='pic-reaction-trigger'>" . htmlspecialchars($reactionLabel) . "</div>";
            echo "<div class='pic-reactions'>";
            echo "<button class='pic-react-btn' type='button'>❤️</button>";
            echo "<button class='pic-react-btn' type='button'>😮</button>";
            echo "<button class='pic-react-btn' type='button'>😂</button>";
            echo "<button class='pic-react-btn' type='button'>😡</button>";
            echo "</div>";
            echo "</div>";
            echo "<button class='pic-download-btn' onclick=\"confirmDownload('" . $imagePath . "')\">Download</button>";
            echo "<button class='pic-delete-btn' onclick=\"deletePicture(" . $memory['id'] . ", '" . $date . "')\">Delete</button>";
            echo "</div>";
        }
        if (!empty($videoPath)) {
            echo "<div class='memory-video-wrap'>";
            echo "<video class='memory-video' controls>";
            echo "<source src='" . htmlspecialchars($videoPath) . "'>";
            echo "Your browser does not support the video element.";
            echo "</video>";
            echo "<div class='video-underlay'>";
            $reactionLabel = isset($memory['reaction']) && $memory['reaction'] !== '' ? $memory['reaction'] : 'React';
            echo "<div class='video-reaction-trigger'>" . htmlspecialchars($reactionLabel) . "</div>";
            echo "<div class='video-reactions'>";
            echo "<button class='video-react-btn' type='button'>❤️</button>";
            echo "<button class='video-react-btn' type='button'>😮</button>";
            echo "<button class='video-react-btn' type='button'>😂</button>";
            echo "<button class='video-react-btn' type='button'>😡</button>";
            echo "</div>";
            echo "<button class='video-delete-btn' onclick=\"deleteVideo(" . $memory['id'] . ", '" . $date . "')\">Delete</button>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div class='memory-pic-card no-photo' data-index='{$index}'>";
        echo "<div class='pic-number-badge'>#{$picNumber}</div>";
        echo "<div class='no-photo-text'>No photo</div>";
        echo "</div>";
    }
}
echo "</div>";

echo "</div><div class='modal-right-column'>"; // Close pictures column, open data column

// Display all data/messages on the right
foreach ($memories as $index => $row) {
    $tagName = ucfirst($row[$titleCol] ?? 'General');
    $moodValue = $moodCol ? trim((string)($row[$moodCol] ?? '')) : '';
    $moodLabel = $moodValue !== '' ? ucfirst($moodValue) : '';
    $cardNumber = $index + 1;
    $isPinned = isset($pinnedIds[intval($row['id'])]);
    $pinnedClass = $isPinned ? ' is-pinned' : '';

    echo "<div class='memory-data-card{$pinnedClass}' data-index='{$index}' style='max-width: 350px;'>";
    echo "<div class='data-header'>";
    echo "<div class='memory-tag-row'>";
    echo "<div class='memory-tag'>{$tagName} #{$cardNumber}</div>";
    if ($moodLabel !== '') {
        echo "<div class='memory-mood'>" . htmlspecialchars($moodLabel) . "</div>";
    }
    echo "</div>";
    echo "<div class='memory-date-time'>";
    echo "<span class='memory-date'>" . date('M d, Y', strtotime($row['created_at'])) . "</span>";
    echo "<span class='memory-time-overlay'>" . date('h:i A', strtotime($row['created_at'])) . "</span>";
    echo "</div>";
    echo "</div>";
    echo '<div class="message-container">';
    $hasImage = !empty($row['image_path']) ? '1' : '0';
    $hasAudio = ($audioCol !== null && !empty($row[$audioCol])) ? '1' : '0';
    $hasVideo = ($videoCol !== null && !empty($row[$videoCol])) ? '1' : '0';
    $reaction = isset($row['reaction']) ? $row['reaction'] : '';
    $moodData = $moodValue !== '' ? $moodValue : '';
    echo '<p class="memory-message" id="message-' . $row['id'] . '" data-date="' . htmlspecialchars($date) . '" data-image-path="' . htmlspecialchars($row['image_path']) . '" data-has-image="' . $hasImage . '" data-has-audio="' . $hasAudio . '" data-has-video="' . $hasVideo . '" data-reaction="' . htmlspecialchars($reaction) . '" data-mood="' . htmlspecialchars($moodData) . '">' . htmlspecialchars($row[$descCol] ?? '') . '</p>';
    if ($audioCol !== null && !empty($row[$audioCol])) {
        echo '<audio controls class="memory-audio-player">';
        echo '<source src="' . htmlspecialchars($row[$audioCol]) . '">';
        echo 'Your browser does not support the audio element.';
        echo '</audio>';
    }
    echo '<div class="message-actions" id="actions-' . $row['id'] . '">';
    $pinLabel = $isPinned ? 'Pinned' : 'Pin';
    echo '<button class="action-btn edit-btn" onclick="toggleEdit(' . $row['id'] . ')" style="padding: 4px 8px; font-size: 11px; background: #ff69b4; color: white; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center;">Edit</button>';
    echo '<button class="action-btn pin-btn" data-id="' . $row['id'] . '" data-pinned="' . ($isPinned ? '1' : '0') . '" style="padding: 4px 8px; font-size: 11px; background: #ffb6d9; color: #b30059; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center;">' . $pinLabel . '</button>';
    echo '<button class="action-btn" onclick="confirmDownload(\'' . $row['image_path'] . '\')" style="padding: 4px 8px; font-size: 11px; background: #ff69b4; color: white; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center;">DOWNLOAD</button>';
    echo '</div>';
    echo '</div>';
    echo "</div>";
}

echo "</div></div>";

function getTagEmoji($tag) {
    $tagEmojis = [
        'general' => 'ðŸŒŸ',
        'birthday' => 'ðŸŽ‚',
        'anniversary' => 'ðŸ’‘',
        'holiday' => 'ðŸŽ‰',
        'travel' => 'âœˆï¸',
        'food' => 'ðŸ”',
        'family' => 'ðŸ‘¨â€ðŸ‘©â€ðŸ‘§â€ðŸ‘¦',
        'friends' => 'ðŸ‘«',
        'work' => 'ðŸ’¼',
        'special' => 'ðŸ’Ž'
    ];
    return $tagEmojis[$tag] ?? 'ðŸŒŸ';
}
?>


