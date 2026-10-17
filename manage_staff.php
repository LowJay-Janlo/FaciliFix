<?php
session_start();
include 'db.php';
include 'partials.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

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
    <link href="base.css" rel="stylesheet">
    <link href="facilities.css" rel="stylesheet">
    <link href="room.css" rel="stylesheet">
    <link href="modal.css" rel="stylesheet">
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

    <script src="layout.js"></script>
    <script>
    (function () {
        const PER_PAGE = 5;

        const rows = Array.from(document.querySelectorAll('.eq-row'));
        if (!rows.length) return;

        const searchInput = document.getElementById('staffSearch');
        const pagination = document.getElementById('pagination');
        const noResults = document.getElementById('noResults');
        const state = { query: '', page: 1 };

        const ARROW_LEFT  = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';
        const ARROW_RIGHT = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';

        function render() {
            const q = state.query.trim().toLowerCase();
            const filtered = rows.filter(function (row) {
                return !q || row.dataset.search.includes(q);
            });

            const pages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
            state.page = Math.min(state.page, pages);
            const start = (state.page - 1) * PER_PAGE;

            rows.forEach(function (row) { row.hidden = true; });
            filtered.slice(start, start + PER_PAGE).forEach(function (row) { row.hidden = false; });

            noResults.hidden = filtered.length > 0;
            buildPagination(pages);
        }

        function makeButton(className, html, label) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = className;
            btn.innerHTML = html;
            btn.setAttribute('aria-label', label);
            return btn;
        }

        function buildPagination(pages) {
            pagination.innerHTML = '';
            if (pages <= 1) {
                pagination.hidden = true;
                return;
            }
            pagination.hidden = false;

            const prev = makeButton('page-arrow', ARROW_LEFT, 'Previous page');
            prev.disabled = state.page === 1;
            prev.addEventListener('click', function () { state.page--; render(); });
            pagination.appendChild(prev);

            for (let i = 1; i <= pages; i++) {
                const btn = makeButton('page-button' + (i === state.page ? ' active' : ''), String(i), 'Page ' + i);
                if (i === state.page) btn.setAttribute('aria-current', 'page');
                btn.addEventListener('click', function () { state.page = i; render(); });
                pagination.appendChild(btn);
            }

            const next = makeButton('page-arrow', ARROW_RIGHT, 'Next page');
            next.disabled = state.page === pages;
            next.addEventListener('click', function () { state.page++; render(); });
            pagination.appendChild(next);
        }

        searchInput.addEventListener('input', function () {
            state.query = this.value;
            state.page = 1;
            render();
        });

        render();
    })();
    </script>

</body>
</html>
