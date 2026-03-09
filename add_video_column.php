<?php
include "db.php";

$sql = "ALTER TABLE memories ADD COLUMN video_path VARCHAR(500) DEFAULT NULL AFTER audio_path";

if ($conn->query($sql) === TRUE) {
    echo "Video column added successfully to memories table";
} else {
    echo "Error adding video column: " . $conn->error;
}

$conn->close();
?>
