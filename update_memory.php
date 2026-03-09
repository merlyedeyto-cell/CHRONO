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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $message = $_POST['message'] ?? '';
    $mood = $_POST['mood'] ?? '';
    if (!isset($_SESSION['user_id'])) {
        echo "Not logged in";
        exit();
    }
    $userId = intval($_SESSION['user_id']);

    $descCol = columnExists($conn, "memories", "description") ? "description" : "message";
    $audioCol = columnExists($conn, "memories", "audio_path") ? "audio_path" : null;
    $videoCol = columnExists($conn, "memories", "video_path") ? "video_path" : null;
    $moodCol = ensureMoodColumn($conn) ? "mood" : null;
    $userCol = ensureUserColumn($conn) ? "user_id" : null;

    // Handle optional media replacement (photo or video)
    $newImagePath = null;
    $newVideoPath = null;
    if (isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            echo "Error uploading media (code " . intval($_FILES['media']['error']) . ")";
            exit();
        }

        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir);
        }

        // Get old image/video to delete
        if ($userCol) {
            $stmtOld = $conn->prepare("SELECT image_path, video_path FROM memories WHERE id = ? AND `$userCol` = ?");
            $stmtOld->bind_param("ii", $id, $userId);
            $stmtOld->execute();
            $oldRes = $stmtOld->get_result();
        } else {
            $oldRes = $conn->query("SELECT image_path, video_path FROM memories WHERE id=" . intval($id));
        }
        if ($oldRes && $oldRes->num_rows > 0) {
            $oldRow = $oldRes->fetch_assoc();
            $oldImage = $oldRow['image_path'];
            $oldVideo = $oldRow['video_path'];
            if ($oldImage && file_exists($oldImage)) {
                unlink($oldImage);
            }
            if ($oldVideo && file_exists($oldVideo)) {
                unlink($oldVideo);
            }
        }
        if (isset($stmtOld)) {
            $stmtOld->close();
        }

        $mediaType = $_FILES['media']['type'] ?? '';
        $mediaName = time() . "_" . basename($_FILES["media"]["name"]);
        $mediaPath = $targetDir . $mediaName;
        if (!move_uploaded_file($_FILES["media"]["tmp_name"], $mediaPath)) {
            echo "Error saving media (move failed)";
            exit();
        }

        if (strpos($mediaType, 'video/') === 0) {
            if ($videoCol === null && ensureVideoColumn($conn)) {
                $videoCol = "video_path";
            }
            if ($videoCol === null) {
                echo "Video not supported yet";
                exit();
            }
            $newVideoPath = $mediaPath;
        } else {
            $newImagePath = $mediaPath;
        }
    }

    // Handle optional audio replacement
    $newAudioPath = null;
    if ($audioCol !== null && isset($_FILES['audio']) && $_FILES['audio']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
            echo "Error uploading audio (code " . intval($_FILES['audio']['error']) . ")";
            exit();
        }

        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir);
        }

        if ($userCol) {
            $stmtOldAudio = $conn->prepare("SELECT audio_path FROM memories WHERE id = ? AND `$userCol` = ?");
            $stmtOldAudio->bind_param("ii", $id, $userId);
            $stmtOldAudio->execute();
            $oldRes = $stmtOldAudio->get_result();
        } else {
            $oldRes = $conn->query("SELECT audio_path FROM memories WHERE id=" . intval($id));
        }
        if ($oldRes && $oldRes->num_rows > 0) {
            $oldRow = $oldRes->fetch_assoc();
            $oldPath = $oldRow['audio_path'];
            if ($oldPath && file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        if (isset($stmtOldAudio)) {
            $stmtOldAudio->close();
        }

        $audioName = time() . "_" . basename($_FILES["audio"]["name"]);
        $newAudioPath = $targetDir . $audioName;
        if (!move_uploaded_file($_FILES["audio"]["tmp_name"], $newAudioPath)) {
            echo "Error saving audio (move failed)";
            exit();
        }
    }

    $moodValue = $moodCol ? mb_substr(trim((string)$mood), 0, 50, 'UTF-8') : null;

    if ($newImagePath !== null && $newAudioPath !== null) {
        $sql = "UPDATE memories SET `$descCol` = ?, " . ($moodCol ? "`$moodCol` = ?, " : "") . "image_path = ?, video_path = NULL, `$audioCol` = ? WHERE id = ?" . ($userCol ? " AND `$userCol` = ?" : "");
        $stmt = $conn->prepare($sql);
        if ($userCol) {
            if ($moodCol) {
                $stmt->bind_param("ssssii", $message, $moodValue, $newImagePath, $newAudioPath, $id, $userId);
            } else {
                $stmt->bind_param("sssii", $message, $newImagePath, $newAudioPath, $id, $userId);
            }
        } else {
            if ($moodCol) {
                $stmt->bind_param("ssssi", $message, $moodValue, $newImagePath, $newAudioPath, $id);
            } else {
                $stmt->bind_param("sssi", $message, $newImagePath, $newAudioPath, $id);
            }
        }
    } elseif ($newVideoPath !== null && $newAudioPath !== null) {
        $sql = "UPDATE memories SET `$descCol` = ?, " . ($moodCol ? "`$moodCol` = ?, " : "") . "image_path = NULL, video_path = ?, `$audioCol` = ? WHERE id = ?" . ($userCol ? " AND `$userCol` = ?" : "");
        $stmt = $conn->prepare($sql);
        if ($userCol) {
            if ($moodCol) {
                $stmt->bind_param("ssssii", $message, $moodValue, $newVideoPath, $newAudioPath, $id, $userId);
            } else {
                $stmt->bind_param("sssii", $message, $newVideoPath, $newAudioPath, $id, $userId);
            }
        } else {
            if ($moodCol) {
                $stmt->bind_param("ssssi", $message, $moodValue, $newVideoPath, $newAudioPath, $id);
            } else {
                $stmt->bind_param("sssi", $message, $newVideoPath, $newAudioPath, $id);
            }
        }
    } elseif ($newImagePath !== null) {
        $sql = "UPDATE memories SET `$descCol` = ?, " . ($moodCol ? "`$moodCol` = ?, " : "") . "image_path = ?, video_path = NULL WHERE id = ?" . ($userCol ? " AND `$userCol` = ?" : "");
        $stmt = $conn->prepare($sql);
        if ($userCol) {
            if ($moodCol) {
                $stmt->bind_param("sssii", $message, $moodValue, $newImagePath, $id, $userId);
            } else {
                $stmt->bind_param("ssii", $message, $newImagePath, $id, $userId);
            }
        } else {
            if ($moodCol) {
                $stmt->bind_param("sssi", $message, $moodValue, $newImagePath, $id);
            } else {
                $stmt->bind_param("ssi", $message, $newImagePath, $id);
            }
        }
    } elseif ($newVideoPath !== null) {
        $sql = "UPDATE memories SET `$descCol` = ?, " . ($moodCol ? "`$moodCol` = ?, " : "") . "image_path = NULL, video_path = ? WHERE id = ?" . ($userCol ? " AND `$userCol` = ?" : "");
        $stmt = $conn->prepare($sql);
        if ($userCol) {
            if ($moodCol) {
                $stmt->bind_param("sssii", $message, $moodValue, $newVideoPath, $id, $userId);
            } else {
                $stmt->bind_param("ssii", $message, $newVideoPath, $id, $userId);
            }
        } else {
            if ($moodCol) {
                $stmt->bind_param("sssi", $message, $moodValue, $newVideoPath, $id);
            } else {
                $stmt->bind_param("ssi", $message, $newVideoPath, $id);
            }
        }
    } elseif ($newAudioPath !== null) {
        $sql = "UPDATE memories SET `$descCol` = ?, " . ($moodCol ? "`$moodCol` = ?, " : "") . "`$audioCol` = ? WHERE id = ?" . ($userCol ? " AND `$userCol` = ?" : "");
        $stmt = $conn->prepare($sql);
        if ($userCol) {
            if ($moodCol) {
                $stmt->bind_param("sssii", $message, $moodValue, $newAudioPath, $id, $userId);
            } else {
                $stmt->bind_param("ssii", $message, $newAudioPath, $id, $userId);
            }
        } else {
            if ($moodCol) {
                $stmt->bind_param("sssi", $message, $moodValue, $newAudioPath, $id);
            } else {
                $stmt->bind_param("ssi", $message, $newAudioPath, $id);
            }
        }
    } else {
        $sql = "UPDATE memories SET `$descCol` = ? " . ($moodCol ? ", `$moodCol` = ? " : "") . "WHERE id = ?" . ($userCol ? " AND `$userCol` = ?" : "");
        $stmt = $conn->prepare($sql);
        if ($userCol) {
            if ($moodCol) {
                $stmt->bind_param("ssii", $message, $moodValue, $id, $userId);
            } else {
                $stmt->bind_param("sii", $message, $id, $userId);
            }
        } else {
            if ($moodCol) {
                $stmt->bind_param("ssi", $message, $moodValue, $id);
            } else {
                $stmt->bind_param("si", $message, $id);
            }
        }
    }

    if ($stmt->execute()) {
        echo "Memory updated successfully!";
    } else {
        echo "Error updating memory: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method";
}
?>

