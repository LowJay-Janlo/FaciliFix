<?php
require 'auth.php';
require_role('Admin');
include 'partials.php';
include 'media.php';


$room_id = (int)($_POST['room_id'] ?? 0);

if ($_SERVER["REQUEST_METHOD"] === "POST" && csrf_valid()) {
    $equipment_id = (int)($_POST['id'] ?? 0);

    if ($equipment_id > 0 && $room_id > 0) {
        $stmt = $conn->prepare("SELECT image FROM equipment WHERE id = ? AND room_id = ?");
        $stmt->bind_param("ii", $equipment_id, $room_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ? AND room_id = ?");
        $stmt->bind_param("ii", $equipment_id, $room_id);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            if ($row && $row['image']) {
                delete_image($row['image']);
            }
            header("Location: room.php?id=" . $room_id . "&deleted=1");
            exit();
        }
    }
}

header("Location: " . ($room_id > 0 ? "room.php?id=" . $room_id : "facilities.php"));
exit();
