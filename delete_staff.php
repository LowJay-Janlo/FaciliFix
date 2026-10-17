<?php
session_start();
include 'db.php';
include 'partials.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && csrf_valid()) {
    $user_id = (int)($_POST['id'] ?? 0);

    if ($user_id > 0) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role <> 'Admin'");
        $stmt->bind_param("i", $user_id);
        $ok = $stmt->execute();
        $stmt->close();

        header("Location: manage_staff.php?" . ($ok ? "deleted=1" : "error=1"));
        exit();
    }
}

header("Location: manage_staff.php");
exit();
