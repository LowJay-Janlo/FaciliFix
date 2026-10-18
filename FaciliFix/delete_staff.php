<?php
require 'auth.php';
require_role('Admin');
include 'partials.php';


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
