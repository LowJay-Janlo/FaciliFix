<?php
session_start();
include 'db.php';
include 'partials.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$error     = '';
$room_name = '';
$room_tag  = '';
$room_icon = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_name = trim($_POST['room_name'] ?? '');
    $room_tag  = $_POST['room_tag'] ?? '';
    $room_icon = $_POST['room_icon'] ?? '';

    if ($room_name === '') {
        $error = "Room name cannot be empty.";
    } elseif (mb_strlen($room_name) > 100) {
        $error = "Room name must be 100 characters or less.";
    } elseif (!in_array($room_tag, $floor_tags, true)) {
        $error = "Please choose a room tag.";
    } elseif (!isset($room_icon_options[$room_icon])) {
        $error = "Please choose a room icon.";
    } else {
        // Block duplicate room names (case-insensitive)
        $check = $conn->prepare("SELECT id FROM rooms WHERE LOWER(room_name) = LOWER(?)");
        $check->bind_param("s", $room_name);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();

        if ($exists) {
            $error = "A room named \"" . $room_name . "\" already exists.";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Room - FaciliFix</title>
    <link href="facilities.css" rel="stylesheet">
    <link href="add_room.css" rel="stylesheet">
    <link href="modal.css" rel="stylesheet">
</head>
<body>

    <?php render_sidebar('facilities'); ?>

    <div class="content">

        <?php render_topbar(); ?>

        <!-- MAIN SECTION -->
        <main class="addroom-main">

            <a href="facilities.php" class="go-back">
                <?php echo icon($icons['arrow-left']); ?> Go Back
            </a>

            <div class="addroom-header">
                <span class="eyebrow">Facilities</span>
                <h1>Add New Room</h1>
                <p>Give the room a name, put it on a floor and pick an icon. It will show up on the Facilities page right away.</p>
            </div>

            <div class="addroom-layout">

                <form method="POST" action="" class="room-form" id="roomForm" novalidate>

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
                        <a href="facilities.php" class="cancel-button">Cancel</a>
                        <button type="submit" class="submit-button">Submit</button>
                    </div>
                </form>

                <!-- LIVE PREVIEW -->
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
    </div>

    <script src="layout.js"></script>
    <script>
    (function () {
        const form = document.getElementById('roomForm');
        const nameInput = document.getElementById('room_name');
        const tagSelect = document.getElementById('room_tag');
        const radios = form.querySelectorAll('input[name="room_icon"]');
        const pvName = document.getElementById('previewName');
        const pvTag = document.getElementById('previewTag');
        const pvIcon = document.getElementById('previewIcon');

        // ---------- Live preview ----------
        function updatePreview() {
            pvName.textContent = nameInput.value.trim() || 'Room name';
            pvTag.textContent = tagSelect.value || 'Select a floor';

            const checked = form.querySelector('input[name="room_icon"]:checked');
            if (checked) {
                pvIcon.innerHTML = checked.closest('.icon-option').querySelector('svg').outerHTML;
                pvIcon.classList.add('filled');
            } else {
                pvIcon.innerHTML = '';
                pvIcon.classList.remove('filled');
            }
        }

        nameInput.addEventListener('input', updatePreview);
        tagSelect.addEventListener('change', updatePreview);
        radios.forEach(function (r) { r.addEventListener('change', updatePreview); });
        updatePreview();

        // ---------- Check the form before it is sent ----------
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