<?php
require 'auth.php';
require_role('Admin');
include 'partials.php';
include 'media.php';


if ($_SERVER["REQUEST_METHOD"] === "POST" && csrf_valid()) {
    $room_id = (int)($_POST['id'] ?? 0);

    if ($room_id > 0) {
        // Note the photos first: the equipment rows disappear together with the room
        $stmt = $conn->prepare("SELECT image FROM equipment WHERE room_id = ? AND image IS NOT NULL");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $images = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'image');
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            array_map('delete_image', $images);
            header("Location: facilities.php?deleted=1");
            exit();
        }
    }
}

header("Location: facilities.php");
exit();
