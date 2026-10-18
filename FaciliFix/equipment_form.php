<?php
require 'auth.php';
require_role('Admin');
include 'partials.php';
include 'media.php';


$room_id = (int)($_GET['room_id'] ?? 0);
$edit_id = (int)($_GET['id'] ?? 0);
$is_edit = $edit_id > 0;

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
$old_image   = '';
$remove_image = false;

if ($is_edit) {
    $stmt = $conn->prepare("SELECT equipment_name, status, description, image FROM equipment WHERE id = ? AND room_id = ?");
    $stmt->bind_param("ii", $edit_id, $room_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        header("Location: room.php?id=" . $room_id);
        exit();
    }
    $old_status = $existing['status'];
    $old_image  = (string)$existing['image'];

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
    $remove_image = $is_edit && !empty($_POST['remove_image']);

    if (!$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        // The upload was bigger than the server allows, so PHP dropped the whole form
        $error = "That upload is too large. Choose an image under 3 MB.";
    } elseif (!csrf_valid()) {
        $error = "Your session expired. Please try again.";
    } elseif ($name === '') {
        $error = "Equipment name cannot be empty.";
    } elseif (mb_strlen($name) > 150) {
        $error = "Equipment name must be 150 characters or less.";
    } elseif (!isset($statuses[$status])) {
        $error = "Please choose a status.";
    } elseif (mb_strlen($description) > 1000) {
        $error = "Description must be 1000 characters or less.";
    } else {
        [$new_image, $image_error] = save_image($_FILES['image'] ?? null);
        if ($image_error !== null) {
            $error = $image_error;
        }
    }

    if ($error === '') {
        $desc_value = ($description === '') ? null : $description;

        // Keep the old photo unless it is replaced or removed
        $image_value = $old_image !== '' ? $old_image : null;
        if ($new_image !== null) {
            $image_value = $new_image;
        } elseif ($remove_image) {
            $image_value = null;
        }

        if ($is_edit) {
            $stmt = $conn->prepare("UPDATE equipment SET equipment_name = ?, status = ?, description = ?, image = ? WHERE id = ? AND room_id = ?");
            $stmt->bind_param("ssssii", $name, $status, $desc_value, $image_value, $edit_id, $room_id);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                if ($old_image !== '' && $old_image !== $image_value) {
                    delete_image($old_image);
                }
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
            if ($new_image !== null) {
                delete_image($new_image);
            }
            error_log("FaciliFix: " . $conn->error);
            $error = "Could not save the changes. Please try again.";
        } else {
            $stmt = $conn->prepare("INSERT INTO equipment (room_id, equipment_name, status, description, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $room_id, $name, $status, $desc_value, $image_value);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: room.php?id=" . $room_id . "&added=1");
                exit();
            }
            if ($new_image !== null) {
                delete_image($new_image);
            }
            error_log("FaciliFix: " . $conn->error);
            $error = "Could not add the equipment. Please try again.";
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
    <link href="css/base.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>

    <?php render_topbar('facilities'); ?>

        <main class="page page-narrow">

            <div class="addroom-header">
                <?php render_back($back_url, 'Back to ' . $room['room_name']); ?>
                <div>
                    <span class="eyebrow"><?php echo htmlspecialchars($room['room_name']); ?></span>
                    <h1><?php echo $is_edit ? 'Update Report' : 'New Equipment'; ?></h1>
                    <p><?php echo $is_edit
                        ? 'Change the equipment details or update its current condition.'
                        : 'Add a piece of equipment to this room and describe its condition.'; ?></p>
                </div>
            </div>

            <div class="addroom-layout single">

                <form method="POST" action="" class="room-form" id="equipmentForm" enctype="multipart/form-data" novalidate>
                    <?php echo csrf_field(); ?>

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
                        <label for="image">Photo</label>
                        <span class="hint">Optional. JPG, PNG or WebP, up to 3 MB.</span>

                        <div class="upload" id="upload">
                            <div class="upload-preview" id="uploadPreview" <?php echo $old_image === '' ? 'hidden' : ''; ?>>
                                <img <?php echo $old_image !== '' ? 'src="' . htmlspecialchars(image_url($old_image)) . '"' : ''; ?>
                                     alt="Photo of the equipment" id="uploadImage" data-original="<?php echo htmlspecialchars(image_url($old_image)); ?>">
                            </div>

                            <input type="file" id="image" name="image" class="visually-hidden" accept="image/jpeg,image/png,image/webp">
                            <label for="image" class="upload-drop">
                                <?php echo icon($icons['upload']); ?>
                                <span id="uploadLabel" data-default="<?php echo $old_image === '' ? 'Choose a photo' : 'Replace photo'; ?>"><?php echo $old_image === '' ? 'Choose a photo' : 'Replace photo'; ?></span>
                            </label>

                            <div class="upload-actions">
                                <button type="button" class="btn btn-gray" id="uploadClear" hidden>Cancel new photo</button>
                                <?php if ($old_image !== ''): ?>
                                    <label class="remove-image" id="removeLabel">
                                        <input type="checkbox" name="remove_image" id="removeImage" value="1" <?php echo $remove_image ? 'checked' : ''; ?>>
                                        Remove current photo
                                    </label>
                                <?php endif; ?>
                            </div>
                            <p class="upload-error" id="uploadError" role="alert" hidden></p>
                        </div>
                    </div>

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

    <script src="js/layout.js"></script>
    <script src="js/equipment_form.js"></script>

</body>
</html>
