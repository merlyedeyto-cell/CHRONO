<?php
include "db.php";

$sql = "ALTER TABLE memories ADD COLUMN audio_path VARCHAR(500) DEFAULT NULL AFTER image_path";

if ($conn->query($sql) === TRUE) {
    echo "Audio column added successfully to memories table";
} else {
    echo "Error adding audio column: " . $conn->error;
}

$conn->close();
?>
