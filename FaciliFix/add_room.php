<?php
require 'auth.php';
require_role('Admin');
include 'partials.php';


$edit_id = (int)($_GET['id'] ?? 0);
$is_edit = $edit_id > 0;

$error     = '';
$room_name = '';
$room_tag  = '';
$room_icon = '';

if ($is_edit && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = $conn->prepare("SELECT room_name, room_tag, room_icon FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        header("Location: facilities.php");
        exit();
    }
    $room_name = $existing['room_name'];
    $room_tag  = (string)$existing['room_tag'];
    $room_icon = $existing['room_icon'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_name = trim($_POST['room_name'] ?? '');
    $room_tag  = $_POST['room_tag'] ?? '';
    $room_icon = $_POST['room_icon'] ?? '';

    if (!csrf_valid()) {
        $error = "Your session expired. Please try again.";
    } elseif ($room_name === '') {
        $error = "Room name cannot be empty.";
    } elseif (mb_strlen($room_name) > 100) {
        $error = "Room name must be 100 characters or less.";
    } elseif (!in_array($room_tag, $floor_tags, true)) {
        $error = "Please choose a room tag.";
    } elseif (!isset($room_icon_options[$room_icon])) {
        $error = "Please choose a room icon.";
    } else {

        $check = $conn->prepare("SELECT id FROM rooms WHERE LOWER(room_name) = LOWER(?) AND id <> ?");
        $check->bind_param("si", $room_name, $edit_id);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();

        if ($exists) {
            $error = "A room named \"" . $room_name . "\" already exists.";
        } elseif ($is_edit) {
            $stmt = $conn->prepare("UPDATE rooms SET room_name = ?, room_tag = ?, room_icon = ? WHERE id = ?");
            $stmt->bind_param("sssi", $room_name, $room_tag, $room_icon, $edit_id);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: room.php?id=" . $edit_id . "&saved=1");
                exit();
            }
            $error = "Error: " . $conn->error;
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO rooms (room_name, room_tag, room_icon) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $room_name, $room_tag, $room_icon);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: facilities.php?added=1");
                exit();
            }
            $error = "Error: " . $conn->error;
            $stmt->close();
        }
    }
}

$back_url = $is_edit ? "room.php?id=" . $edit_id : "facilities.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Room' : 'Add Room'; ?> - FaciliFix</title>
    <link href="css/base.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>

    <?php render_topbar('facilities'); ?>

        <main class="page page-narrow">

            <div class="addroom-header">
                <?php render_back($back_url, 'Go back'); ?>
                <div>
                    <span class="eyebrow">Facilities</span>
                    <h1><?php echo $is_edit ? 'Edit Room' : 'Add New Room'; ?></h1>
                    <p><?php echo $is_edit
                        ? 'Change the room name, floor or icon. Its equipment stays exactly as it is.'
                        : 'Give the room a name, put it on a floor and pick an icon. It will show up on the Facilities page right away.'; ?></p>
                </div>
            </div>

            <div class="addroom-layout">

                <form method="POST" action="" class="room-form" id="roomForm" novalidate>
                    <?php echo csrf_field(); ?>

                    <?php if ($error !== ''): ?>
                        <div class="notice notice-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="room_name">Room Name</label>
                        <span class="hint">The name people will search for, e.g. Room 301 or Library 003.</span>
                        <input type="text" id="room_name" name="room_name" maxlength="100"
                               placeholder="Enter room name"
                               value="<?php echo htmlspecialchars($room_name); ?>" required autofocus>
                    </div>

                    <div class="field">
                        <label for="room_tag">Room Tag</label>
                        <span class="hint">Which floor the room is on. Used by the filter buttons.</span>
                        <select id="room_tag" name="room_tag" required>
                            <option value="" disabled <?php echo $room_tag === '' ? 'selected' : ''; ?>>Select a floor</option>
                            <?php foreach ($floor_tags as $tag): ?>
                                <option value="<?php echo htmlspecialchars($tag); ?>" <?php echo $room_tag === $tag ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tag); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <fieldset class="field icon-picker">
                        <legend>Room Icon</legend>
                        <span class="hint">Pick the type of room.</span>
                        <div class="icon-options">
                            <?php foreach ($room_icon_options as $key => $opt): ?>
                                <label class="icon-option">
                                    <input type="radio" name="room_icon" value="<?php echo $key; ?>"
                                           <?php echo $room_icon === $key ? 'checked' : ''; ?> required>
                                    <span class="tile">
                                        <span class="badge"><?php echo icon($icons[$opt['icon']]); ?></span>
                                        <span><?php echo htmlspecialchars($opt['label']); ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <a href="<?php echo $back_url; ?>" class="cancel-button">Cancel</a>
                        <button type="submit" class="submit-button"><?php echo $is_edit ? 'Save Changes' : 'Submit'; ?></button>
                    </div>
                </form>

                <aside class="preview" aria-label="Room card preview">
                    <h2>Preview</h2>
                    <p>This is how the room will look on the Facilities page.</p>
                    <div class="preview-card">
                        <span class="preview-icon" id="previewIcon"></span>
                        <span class="preview-text">
                            <strong id="previewName">Room name</strong>
                            <span id="previewTag">Select a floor</span>
                        </span>
                        <?php echo icon($icons['arrow-right']); ?>
                    </div>
                </aside>

            </div>

        </main>

    <script src="js/layout.js"></script>
    <script src="js/add_room.js"></script>

</body>
</html>