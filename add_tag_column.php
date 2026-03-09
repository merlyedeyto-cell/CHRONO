<?php
include "db.php";

// Add tag column to memories table
$sql = "ALTER TABLE memories ADD COLUMN tag VARCHAR(50) DEFAULT 'general' AFTER message";

if ($conn->query($sql) === TRUE) {
    echo "Tag column added successfully to memories table";
} else {
    echo "Error adding tag column: " . $conn->error;
}

$conn->close();
?>
