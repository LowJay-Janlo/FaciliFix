<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$room_id = (int)($_POST['room_id'] ?? 0);

// Only accept POST so a link or page refresh can never delete equipment
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $equipment_id = (int)($_POST['id'] ?? 0);

    if ($equipment_id > 0 && $room_id > 0) {
        $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ? AND room_id = ?");
        $stmt->bind_param("ii", $equipment_id, $room_id);
        $stmt->execute();
        $stmt->close();

        header("Location: room.php?id=" . $room_id . "&deleted=1");
        exit();
    }
}

header("Location: " . ($room_id > 0 ? "room.php?id=" . $room_id : "facilities.php"));
exit();
