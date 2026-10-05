<?php
session_start();
include 'db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard - FaciliFix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary px-4">
        <a class="navbar-brand" href="#">FaciliFix - Staff Portal</a>
        <div class="ms-auto">
            <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-3">Campus Rooms & Facilities</h2>
        <p class="text-muted mb-4">Select a room below to view its equipment or report a maintenance issue.</p>

        <div class="row">
            <?php if ($rooms->num_rows > 0): ?>
                <?php while($room = $rooms->fetch_assoc()): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm p-3">
                            <h5 class="card-title text-primary"><?php echo htmlspecialchars($room['room_name']); ?></h5>
                            <p class="card-text text-muted mb-3">Building: <?php echo htmlspecialchars($room['building']); ?></p>
                            <a href="room_details.php?room_id=<?php echo $room['id']; ?>" class="btn btn-outline-primary btn-sm">View Room Equipment</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12"><p class="text-center text-muted">No rooms available yet. Ask the Admin to add rooms.</p></div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
