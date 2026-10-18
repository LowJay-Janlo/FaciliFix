<?php
require 'auth.php';
require_role('Admin');
include 'partials.php';


$edit_id = (int)($_GET['id'] ?? 0);
$is_edit = $edit_id > 0;

$error = '';
$name  = '';
$email = '';

if ($is_edit) {
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? AND role <> 'Admin'");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        header("Location: manage_staff.php");
        exit();
    }
    $name  = $existing['name'];
    $email = $existing['email'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $error = "Your session expired. Please try again.";
    } elseif ($name === '') {
        $error = "Name cannot be empty.";
    } elseif (mb_strlen($name) > 100) {
        $error = "Name must be 100 characters or less.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $error = "Please enter a valid email address (100 characters or less).";
    } elseif (!$is_edit && $password === '') {
        $error = "Please enter a password.";
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        // Block duplicate emails, ignoring the user being edited
        $check = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id <> ?");
        $check->bind_param("si", $email, $edit_id);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();

        if ($exists) {
            $error = "An account with that email already exists.";
        } elseif ($is_edit) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role <> 'Admin'");
                $stmt->bind_param("sssi", $name, $email, $hash, $edit_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role <> 'Admin'");
                $stmt->bind_param("ssi", $name, $email, $edit_id);
            }
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                header("Location: manage_staff.php?saved=1");
                exit();
            }
            error_log("FaciliFix: " . $conn->error);
            $error = "Could not save the changes. Please try again.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'Staff';
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hash, $role);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                header("Location: manage_staff.php?added=1");
                exit();
            }
            error_log("FaciliFix: " . $conn->error);
            $error = "Could not add the user. Please try again.";
        }
    }
}

$back_url = "manage_staff.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit User' : 'Add New User'; ?> - FaciliFix</title>
    <link href="css/base.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>

    <?php render_topbar('staff'); ?>

    <main class="page page-narrow">

        <div class="addroom-header">
            <?php render_back($back_url, 'Back to account management'); ?>
            <div>
                <span class="eyebrow">Account Management</span>
                <h1><?php echo $is_edit ? 'Edit User' : 'Add New User'; ?></h1>
                <p><?php echo $is_edit
                    ? 'Change the name or email, or set a new password. Leave the password empty to keep the current one.'
                    : 'Create a staff account. They can log in with this email and password right away.'; ?></p>
            </div>
        </div>

        <div class="addroom-layout single">

            <form method="POST" action="" class="room-form" id="staffForm" novalidate>
                <?php echo csrf_field(); ?>

                <?php if ($error !== ''): ?>
                    <div class="notice notice-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="field">
                    <label for="name">User Name</label>
                    <span class="hint">The full name shown on reports.</span>
                    <input type="text" id="name" name="name" maxlength="100"
                           placeholder="Enter full name"
                           value="<?php echo htmlspecialchars($name); ?>" required autofocus>
                </div>

                <div class="field">
                    <label for="email">User Gmail</label>
                    <span class="hint">They will use this email to log in.</span>
                    <input type="email" id="email" name="email" maxlength="100"
                           placeholder="Test@Gmail.com"
                           value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="field">
                    <label for="password">User Password</label>
                    <span class="hint"><?php echo $is_edit
                        ? 'Leave empty to keep the current password. At least 8 characters if you change it.'
                        : 'At least 8 characters.'; ?></span>
                    <input type="password" id="password" name="password" minlength="8" autocomplete="new-password"
                           placeholder="<?php echo $is_edit ? 'Leave empty to keep current password' : 'Password'; ?>"
                           <?php echo $is_edit ? '' : 'required'; ?>>
                    <label class="show-password"><input type="checkbox" id="showPassword"> Show password</label>
                </div>

                <div class="form-actions">
                    <a href="<?php echo $back_url; ?>" class="cancel-button">Cancel</a>
                    <button type="submit" class="submit-button"><?php echo $is_edit ? 'Save Changes' : 'Submit'; ?></button>
                </div>
            </form>

        </div>

    </main>

    <script src="js/layout.js"></script>
    <script src="js/staff_form.js"></script>

</body>
</html>
