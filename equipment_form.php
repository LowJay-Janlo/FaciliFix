<?php
session_start();
include 'db.php';
include 'partials.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$room_id = (int)($_GET['room_id'] ?? 0);
$edit_id = (int)($_GET['id'] ?? 0);
$is_edit = $edit_id > 0;

// The room this equipment belongs to
$stmt = $conn->prepare("SELECT id, room_name FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    header("Location: facilities.php");
    exit();
}

$statuses = [
    'Working' => 'check-circle',
    'Broken'  => 'x-circle',
    'Missing' => 'help-circle',
];

$error       = '';
$name        = '';
$status      = '';
$description = '';
$old_status  = '';

// Editing: load the existing record
if ($is_edit) {
    $stmt = $conn->prepare("SELECT equipment_name, status, description FROM equipment WHERE id = ? AND room_id = ?");
    $stmt->bind_param("ii", $edit_id, $room_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        header("Location: room.php?id=" . $room_id);
        exit();
    }
    $old_status = $existing['status'];

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        $name        = $existing['equipment_name'];
        $status      = $existing['status'];
        $description = (string)$existing['description'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name        = trim($_POST['equipment_name'] ?? '');
    $status      = $_POST['status'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = "Equipment name cannot be empty.";
    } elseif (mb_strlen($name) > 150) {
        $error = "Equipment name must be 150 characters or less.";
    } elseif (!isset($statuses[$status])) {
        $error = "Please choose a status.";
    } elseif (mb_strlen($description) > 1000) {
        $error = "Description must be 1000 characters or less.";
    } else {
        $desc_value = ($description === '') ? null : $description;

        if ($is_edit) {
            $stmt = $conn->prepare("UPDATE equipment SET equipment_name = ?, status = ?, description = ? WHERE id = ? AND room_id = ?");
            $stmt->bind_param("sssii", $name, $status, $desc_value, $edit_id, $room_id);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                // Keep a history entry in the reports table when the status changed
                if ($status !== $old_status) {
                    $user_id = (int)$_SESSION['user_id'];
                    $log = $conn->prepare("INSERT INTO reports (equipment_id, user_id, status_reported, remarks) VALUES (?, ?, ?, ?)");
                    $log->bind_param("iiss", $edit_id, $user_id, $status, $desc_value);
                    $log->execute();
                    $log->close();
                }
                header("Location: room.php?id=" . $room_id . "&saved=1");
                exit();
            }
            $error = "Error: " . $conn->error;
        } else {
            $stmt = $conn->prepare("INSERT INTO equipment (room_id, equipment_name, status, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $room_id, $name, $status, $desc_value);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: room.php?id=" . $room_id . "&added=1");
                exit();
            }
            $error = "Error: " . $conn->error;
            $stmt->close();
        }
    }
}

$back_url = "room.php?id=" . $room_id;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Update Report' : 'New Equipment'; ?> - FaciliFix</title>
    <link href="facilities.css" rel="stylesheet">
    <link href="add_room.css" rel="stylesheet">
    <link href="room.css" rel="stylesheet">
    <link href="modal.css" rel="stylesheet">
</head>
<body>

    <?php render_sidebar('facilities'); ?>

    <div class="content">

        <?php render_topbar(); ?>

        <main class="addroom-main">

            <a href="<?php echo $back_url; ?>" class="go-back">
                <?php echo icon($icons['arrow-left']); ?> Go Back
            </a>

            <div class="addroom-header">
                <span class="eyebrow"><?php echo htmlspecialchars($room['room_name']); ?></span>
                <h1><?php echo $is_edit ? 'Update Report' : 'New Equipment'; ?></h1>
                <p><?php echo $is_edit
                    ? 'Change the equipment details or update its current condition.'
                    : 'Add a piece of equipment to this room and describe its condition.'; ?></p>
            </div>

            <div class="addroom-layout single">

                <form method="POST" action="" class="room-form" id="equipmentForm" novalidate>

                    <?php if ($error !== ''): ?>
                        <div class="notice notice-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="equipment_name">Equipment Name</label>
                        <span class="hint">e.g. Computer, Electric Fan, TV Remote.</span>
                        <input type="text" id="equipment_name" name="equipment_name" maxlength="150"
                               placeholder="Enter equipment name"
                               value="<?php echo htmlspecialchars($name); ?>" required autofocus>
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
                        <span class="hint">Optional. Serial number, what is wrong, where it was last seen...</span>
                        <textarea id="description" name="description" maxlength="1000"
                                  placeholder="Write a description or comment"><?php echo htmlspecialchars($description); ?></textarea>
                        <span class="char-count"><span id="charCount">0</span> / 1000</span>
                    </div>

                    <div class="form-actions">
                        <a href="<?php echo $back_url; ?>" class="cancel-button">Cancel</a>
                        <button type="submit" class="submit-button"><?php echo $is_edit ? 'Save Changes' : 'Add Equipment'; ?></button>
                    </div>
                </form>

            </div>

        </main>
    </div>

    <script src="layout.js"></script>
    <script>
    (function () {
        const form = document.getElementById('equipmentForm');
        const desc = document.getElementById('description');
        const count = document.getElementById('charCount');

        function updateCount() { count.textContent = desc.value.length; }
        desc.addEventListener('input', updateCount);
        updateCount();

        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
            }
        });
    })();
    </script>

</body>
</html>
