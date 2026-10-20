<?php
require 'auth.php';
require_role('Staff');
include 'partials.php';
include 'media.php';

$room_id = (int)($_GET['room_id'] ?? 0);
$eq_id   = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT e.id, e.equipment_name, e.status, e.description, e.image, r.room_name
     FROM equipment e JOIN rooms r ON r.id = e.room_id
     WHERE e.id = ? AND e.room_id = ?"
);
$stmt->bind_param("ii", $eq_id, $room_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    header("Location: " . ($room_id > 0 ? "room_details.php?room_id=" . $room_id : "staff_dashboard.php"));
    exit();
}

$statuses = [
    'Working' => 'check-circle',
    'Broken'  => 'x-circle',
    'Missing' => 'help-circle',
];

$error       = '';
$status      = $item['status'];
$description = (string)$item['description'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $status      = $_POST['status'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (!csrf_valid()) {
        $error = "Your session expired. Please try again.";
    } elseif (!isset($statuses[$status])) {
        $error = "Please choose a status.";
    } elseif (mb_strlen($description) > 1000) {
        $error = "Comments must be 1000 characters or less.";
    } else {
        $desc_value = ($description === '') ? null : $description;
        $user_id    = (int)$_SESSION['user_id'];

        // Staff can only change the condition and the comments, nothing else about the equipment
        $stmt = $conn->prepare("UPDATE equipment SET status = ?, description = ? WHERE id = ? AND room_id = ?");
        $stmt->bind_param("ssii", $status, $desc_value, $eq_id, $room_id);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $log = $conn->prepare("INSERT INTO reports (equipment_id, user_id, status_reported, remarks) VALUES (?, ?, ?, ?)");
            $log->bind_param("iiss", $eq_id, $user_id, $status, $desc_value);
            $log->execute();
            $log->close();

            header("Location: room_details.php?room_id=" . $room_id . "&reported=1");
            exit();
        }
        error_log("FaciliFix: " . $conn->error);
        $error = "Could not submit the report. Please try again.";
    }
}

$back_url = "room_details.php?room_id=" . $room_id;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Report - FaciliFix</title>
    <link href="css/base.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>

    <?php render_topbar('rooms', true, 'Staff'); ?>

        <main class="page page-narrow">

            <div class="addroom-header">
                <?php render_back($back_url, 'Back to ' . $item['room_name']); ?>
                <div>
                    <span class="eyebrow"><?php echo htmlspecialchars($item['room_name']); ?></span>
                    <h1>Update Report</h1>
                    <p>Tell us the current condition of this equipment.</p>
                </div>
            </div>

            <div class="addroom-layout single">

                <form method="POST" action="" class="room-form" id="reportForm" novalidate>
                    <?php echo csrf_field(); ?>

                    <?php if ($error !== ''): ?>
                        <div class="notice notice-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="eq-summary">
                        <?php if ($item['image']): ?>
                            <span class="thumb"><img src="<?php echo htmlspecialchars(image_url($item['image'])); ?>" alt=""></span>
                        <?php else: ?>
                            <span class="thumb thumb-empty"><?php echo icon($icons['image']); ?></span>
                        <?php endif; ?>
                        <span>
                            <strong><?php echo htmlspecialchars($item['equipment_name']); ?></strong>
                            <small>ID <?php echo (int)$item['id']; ?></small>
                        </span>
                    </div>

                    <fieldset class="field icon-picker">
                        <legend>Status</legend>
                        <span class="hint">What condition is it in right now?</span>
                        <div class="icon-options status-options">
                            <?php foreach ($statuses as $label => $icon_name): ?>
                                <label class="icon-option status-<?php echo strtolower($label); ?>">
                                    <input type="radio" name="status" value="<?php echo $label; ?>"
                                           <?php echo $status === $label ? 'checked' : ''; ?> required>
                                    <span class="tile">
                                        <span class="badge"><?php echo icon($icons[$icon_name]); ?></span>
                                        <span><?php echo $label; ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <div class="field">
                        <label for="description">Description / Comments</label>
                        <span class="hint">Optional. What is wrong, where it was last seen...</span>
                        <textarea id="description" name="description" maxlength="1000"
                                  placeholder="Write a description or comment"><?php echo htmlspecialchars($description); ?></textarea>
                        <span class="char-count"><span id="charCount">0</span> / 1000</span>
                    </div>

                    <div class="form-actions">
                        <a href="<?php echo $back_url; ?>" class="cancel-button">Cancel</a>
                        <button type="submit" class="submit-button">Submit Report</button>
                    </div>
                </form>

            </div>

        </main>

    <script src="js/layout.js"></script>
    <script src="js/report_form.js"></script>

</body>
</html>
