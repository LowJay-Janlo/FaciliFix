<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

$room_id = $_GET['room_id'] ?? null;
if (!$room_id) {
    header("Location: staff_dashboard.php");
    exit();
}

$room_stmt = $conn->prepare("SELECT room_name, building FROM rooms WHERE id = ?");
$room_stmt->bind_param("i", $room_id);
$room_stmt->execute();
$room = $room_stmt->get_result()->fetch_assoc();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_id = $_POST['equipment_id'];
    $status_reported = $_POST['status_reported'];
    $remarks = trim($_POST['remarks']);
    $user_id = $_SESSION['user_id'];

    if (!empty($equipment_id) && !empty($status_reported)) {

        $rep_stmt = $conn->prepare("INSERT INTO reports (equipment_id, user_id, status_reported, remarks) VALUES (?, ?, ?, ?)");
        $rep_stmt->bind_param("iiss", $equipment_id, $user_id, $status_reported, $remarks);
        
        if ($rep_stmt->execute()) {

            $up_stmt = $conn->prepare("UPDATE equipment SET status = ? WHERE id = ?");
            $up_stmt->bind_param("si", $status_reported, $equipment_id);
            $up_stmt->execute();
            $up_stmt->close();

            $message = "Report submitted successfully! Maintenance has been notified.";
        } else {
            $message = "Error submitting report.";
        }
        $rep_stmt->close();
    } else {
        $message = "Please select an equipment item and status.";
    }
}

$eq_stmt = $conn->prepare("SELECT * FROM equipment WHERE room_id = ?");
$eq_stmt->bind_param("i", $room_id);
$eq_stmt->execute();
$equipment_list = $eq_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($room['room_name']); ?> - FaciliFix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container my-5">
        <a href="staff_dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Rooms</a>
        <h2><?php echo htmlspecialchars($room['room_name']); ?></h2>
        <p class="text-muted">Building: <?php echo htmlspecialchars($room['building']); ?></p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card shadow p-4 mb-4">
            <h4>Report Equipment Issue</h4>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Select Equipment</label>
                    <select name="equipment_id" class="form-control" required>
                        <option value="">-- Select Item --</option>
                        <?php 

                        $eq_dropdown = $conn->query("SELECT id, equipment_name FROM equipment WHERE room_id = $room_id");
                        while($eq = $eq_dropdown->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['equipment_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Condition Status</label>
                    <select name="status_reported" class="form-control" required>
                        <option value="Working">Working</option>
                        <option value="Broken">Broken</option>
                        <option value="Missing">Missing</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks / Description</label>
                    <textarea name="remarks" class="form-control" placeholder="Describe the issue (e.g., fan blade is detached, screen won't turn on)" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-danger">Submit Report</button>
            </form>
        </div>

        <div class="card shadow p-4">
            <h4>Current Equipment List in this Room</h4>
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>Equipment Name</th>
                        <th>Current Status</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($equipment_list->num_rows > 0): ?>
                        <?php while($item = $equipment_list->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                <td>
                                    <?php if ($item['status'] == 'Working'): ?>
                                        <span class="badge bg-success">Working</span>
                                    <?php elseif ($item['status'] == 'Broken'): ?>
                                        <span class="badge bg-danger">Broken</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['created_at']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center">No equipment assigned to this room yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>