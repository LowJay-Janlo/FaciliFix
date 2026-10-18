<?php
require 'auth.php';
require_role('Admin');
include 'partials.php';


// Staff accounts = every user that is not an Admin
$result = $conn->query("SELECT id, name, email FROM users WHERE role <> 'Admin' ORDER BY id DESC");
$staff = [];
while ($result && ($row = $result->fetch_assoc())) {
    $staff[] = $row;
}

$notices = [
    'added'   => ['success', 'User added successfully!'],
    'saved'   => ['success', 'Changes saved!'],
    'deleted' => ['success', 'User deleted.'],
    'error'   => ['error',   'That user could not be deleted. Please try again.'],
];
$notice = null;
foreach ($notices as $key => $n) {
    if (isset($_GET[$key])) { $notice = $n; break; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management - FaciliFix</title>
    <link href="css/base.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>

    <?php render_topbar('staff'); ?>

    <main class="page">

        <?php if ($notice): ?>
            <div class="notice notice-<?php echo $notice[0]; ?>" role="<?php echo $notice[0] === 'error' ? 'alert' : 'status'; ?>">
                <?php if ($notice[0] === 'success') echo icon($icons['check']); ?>
                <?php echo htmlspecialchars($notice[1]); ?>
            </div>
        <?php endif; ?>

        <div class="page-head">
            <?php render_back('admin_dashboard.php', 'Back to dashboard'); ?>
            <h1>Account Management</h1>
        </div>
        <hr class="page-divider">

        <div class="toolbar">
            <div class="search-box">
                <input type="text" id="staffSearch" placeholder="Filter by name or email..." autocomplete="off" aria-label="Filter users">
                <?php echo icon($icons['search']); ?>
            </div>
            <a href="staff_form.php" class="btn btn-gray">
                <?php echo icon($icons['plus']); ?> New User
            </a>
        </div>

        <div class="table-wrap">
            <table class="equipment-table staff-table">
                <thead>
                    <tr>
                        <th>User Id</th>
                        <th>Name</th>
                        <th>User Email</th>
                        <th>User Password</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $user): ?>
                        <tr class="eq-row" data-search="<?php echo htmlspecialchars(strtolower($user['id'] . ' ' . $user['name'] . ' ' . $user['email'])); ?>">
                            <td><?php echo (int)$user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td class="staff-email"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="pw-mask" title="Passwords are stored securely and cannot be shown">••••••••</span></td>
                            <td>
                                <div class="row-actions">
                                    <a href="staff_form.php?id=<?php echo (int)$user['id']; ?>" class="btn btn-gray btn-block">Update User</a>
                                    <form method="POST" action="delete_staff.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                        <button type="submit" class="btn btn-red btn-block"
                                                data-confirm-title="Delete this user?"
                                                data-confirm="Delete the account &quot;<?php echo htmlspecialchars($user['email']); ?>&quot;? They will no longer be able to log in, and the reports they submitted will be deleted too. This cannot be undone."
                                                data-confirm-ok="Delete User">Delete User</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (count($staff) === 0): ?>
                <p class="empty-message">No staff accounts yet. Click <strong>New User</strong> to create one.</p>
            <?php endif; ?>
            <p class="empty-message" id="noResults" hidden>No users match your filter.</p>
        </div>

        <nav class="pagination" id="pagination" aria-label="User pages" hidden></nav>

    </main>

    <script src="js/layout.js"></script>
    <script src="js/manage_staff.js"></script>

</body>
</html>
