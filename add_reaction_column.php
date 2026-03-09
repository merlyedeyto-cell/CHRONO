<?php
include "db.php";

$sql = "ALTER TABLE memories ADD COLUMN reaction VARCHAR(16) DEFAULT NULL AFTER description";

if ($conn->query($sql) === TRUE) {
    echo "Reaction column added successfully to memories table";
} else {
    echo "Error adding reaction column: " . $conn->error;
}

$conn->close();
?>
