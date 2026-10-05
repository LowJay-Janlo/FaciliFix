<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FaciliFix - Admin Dashboard</title>
    <link href="dashboard.css" rel="stylesheet">
</head>
<body>

    <nav>
        <div class="nav-brand">
            <img src="Assets/Images/logo.png" alt="FaciliFix Logo" class="logo" draggable="false">
        </div>
        <div>
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </nav>

    <main class="dashboard-main">
        <div class="dashboard-heading">
            <h1>Admin Dashboard</h1>
            <hr class="dashboard-divider">
        </div>

        <section class="dashboard-grid" aria-label="Admin management">
            <a class="dashboard-card" href="facilities.php">
                <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&amp;fit=crop&amp;w=900&amp;q=80" alt="Bright room with tables and chairs" loading="lazy" draggable="false">
                <div class="dashboard-card-content">
                    <h2>Facilities</h2>
                    <p>Select any room to view assigned equipment, monitor real-time statuses, or easily add new rooms and equipment to keep campus facilities organized.</p>
                </div>
            </a>

            <a class="dashboard-card" href="manage_equipment.php">
                <img src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&amp;fit=crop&amp;w=900&amp;q=80" alt="Maintenance professional working on facility equipment" loading="lazy" draggable="false">
                <div class="dashboard-card-content">
                    <h2>Manage Equipment</h2>
                    <p>Submit a report to easily update the current status of any campus equipment as working, broken, or missing.</p>
                </div>
            </a>

            <a class="dashboard-card" href="manage_staff.php">
                <img src="https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&amp;fit=crop&amp;w=900&amp;q=80" alt="Staff collaborating around a table" loading="lazy" draggable="false">
                <div class="dashboard-card-content">
                    <h2>Manage Staff Accounts</h2>
                    <p>Manage staff accounts by creating new profiles, viewing existing user details, updating permissions, or removing inactive accounts.</p>
                </div>
            </a>
        </section>
    </main>

</body>
</html>