<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $hashed_password, $role);
        $stmt->fetch();

        if ($password == $hashed_password) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['role'] = $role;

            if ($role == 'Admin') {
                header("Location: admin_dashboard.php");
                exit();
            } else {
                header("Location: staff_dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FaciliFix - Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="login.css" rel="stylesheet">
</head>
<body >
    <header>
        <nav class="login-nav"></nav>
    </header>

    <main class="login-main">
        <div class="login-content">
            <img src="Assets/Images/LogoSlogan.png" alt="FaciliFix" class="login-logo"  draggable="false">
            <div class="login-form">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="login-form-header">
                    <h2>Login</h2>
                    <p>Welcome back! Please enter your details.</p>
                <form method="POST" action="">
                    <div>
                        <label class="LoginLabel">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                    </div>
                    <div>
                        <label class="LoginLabel">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    <button type="submit">Login</button>
                </div>
                </form>
            </div>
        </div>
    </main>

</body>
</html>
