<?php
session_start();
include 'db.php';
include 'partials.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && csrf_valid()) {
    $room_id = (int)($_POST['id'] ?? 0);

    if ($room_id > 0) {
        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $stmt->close();

        header("Location: facilities.php?deleted=1");
        exit();
    }
}

header("Location: facilities.php");
exit();
