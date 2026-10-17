<?php
session_start();
include 'db.php';
include 'partials.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . (($_SESSION['role'] ?? '') === 'Admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $stored_password, $role);
        $stmt->fetch();

        $valid = false;
        $needs_upgrade = false;

        if (password_get_info($stored_password)['algo'] !== null) {
            $valid = password_verify($password, $stored_password);
            $needs_upgrade = $valid && password_needs_rehash($stored_password, PASSWORD_DEFAULT);
        } else {
            $valid = hash_equals($stored_password, $password);
            $needs_upgrade = $valid;
        }

        if ($valid) {
            if ($needs_upgrade) {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $up->bind_param("si", $new_hash, $id);
                $up->execute();
                $up->close();
            }

            session_regenerate_id(true);
            $_SESSION['user_id']   = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['role']      = $role;

            if ($role == 'Admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: staff_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaciliFix - Login</title>

    <link href="base.css" rel="stylesheet">
    <link href="login.css" rel="stylesheet">
</head>
<body>
    <?php render_topbar(null, false); ?>

    <main class="login-main">
        <div class="login-content">
            <img src="Assets/Images/LogoSlogan.png" alt="FaciliFix" class="login-logo" draggable="false">
            <div class="login-form">
                <?php if (!empty($error)): ?>
                    <div class="notice notice-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <h2>Login</h2>
                <p class="subtitle">Welcome back! Please enter your details.</p>

                <form method="POST" action="">
                    <div class="field">
                        <label class="LoginLabel" for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="example@email.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
                    </div>
                    <div class="field">
                        <label class="LoginLabel" for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    <button type="submit">Login</button>
                </form>
            </div>
        </div>
    </main>

</body>
</html>
