<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$query = "
    SELECT 
        reports.id AS report_id,
        equipment.equipment_name,
        rooms.room_name,
        users.name AS reporter_name,
        reports.status_reported,
        reports.remarks,
        reports.created_at
    FROM reports
    JOIN equipment ON reports.equipment_id = equipment.id
    JOIN rooms ON equipment.room_id = rooms.id
    JOIN users ON reports.user_id = users.id
    ORDER BY reports.created_at DESC
";

$reports_result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Reports - FaciliFix Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
        <a class="navbar-brand" href="admin_dashboard.php">FaciliFix Admin</a>
        <div class="ms-auto">
            <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container my-5">
        <a href="admin_dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
        <h2>Maintenance & Equipment Reports</h2>
        <p class="text-muted">Real-time log of all issues reported by teachers and staff across campus.</p>

        <div class="card shadow p-4">
            <h4>Incoming Issues & History Log</h4>
            <table class="table table-striped table-hover mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Room</th>
                        <th>Equipment</th>
                        <th>Reported Status</th>
                        <th>Remarks / Issue</th>
                        <th>Reported By</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reports_result->num_rows > 0): ?>
                        <?php while($row = $reports_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['report_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['room_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                                <td>
                                    <?php if ($row['status_reported'] == 'Working'): ?>
                                        <span class="badge bg-success">Working</span>
                                    <?php elseif ($row['status_reported'] == 'Broken'): ?>
                                        <span class="badge bg-danger">Broken</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['remarks'] ?: 'No remarks provided'); ?></td>
                                <td><?php echo htmlspecialchars($row['reporter_name']); ?></td>
                                <td><?php echo $row['created_at']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No maintenance reports found yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>