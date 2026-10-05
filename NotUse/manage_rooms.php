<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_name = trim($_POST['room_name']);
    $building = trim($_POST['building']);

    if (!empty($room_name)) {
        $stmt = $conn->prepare("INSERT INTO rooms (room_name, building) VALUES (?, ?)");
        $stmt->bind_param("ss", $room_name, $building);
        
        if ($stmt->execute()) {
            $message = "Room added successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Room name cannot be empty.";
    }
}

$result = $conn->query("SELECT * FROM rooms ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Rooms - FaciliFix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container my-5">
        <a href="admin_dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
        <h2>Manage Rooms</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card shadow p-4 mb-4">
            <h4>Add New Room</h4>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Room Name / Number</label>
                    <input type="text" name="room_name" class="form-control" placeholder="e.g., Computer Lab 1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Building / Location</label>
                    <input type="text" name="building" class="form-control" placeholder="e.g., IT Building">
                </div>
                <button type="submit" class="btn btn-primary">Add Room</button>
            </form>
        </div>

        <div class="card shadow p-4">
            <h4>Existing Rooms</h4>
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Room Name</th>
                        <th>Building</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['room_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['building']); ?></td>
                                <td><?php echo $row['created_at']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No rooms found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>