<?php
session_start();
include 'db.php';
include 'partials.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$room_id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT id, room_name, room_tag, room_icon FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    header("Location: facilities.php");
    exit();
}

$eq_stmt = $conn->prepare(
    "SELECT id, equipment_name, status, description, created_at, updated_at
     FROM equipment WHERE room_id = ? ORDER BY id DESC"
);
$eq_stmt->bind_param("i", $room_id);
$eq_stmt->execute();
$equipment = $eq_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$eq_stmt->close();

$icon_key = $room_icon_options[$room['room_icon']]['icon'] ?? 'graduation';

$notices = [
    'added'   => 'Equipment added successfully!',
    'saved'   => 'Changes saved!',
    'deleted' => 'Equipment deleted.',
];
$notice = '';
foreach ($notices as $key => $text) {
    if (isset($_GET[$key])) { $notice = $text; break; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['room_name']); ?> - FaciliFix</title>
    <link href="facilities.css" rel="stylesheet">
    <link href="room.css" rel="stylesheet">
    <link href="modal.css" rel="stylesheet">
</head>
<body>

    <?php render_sidebar('facilities'); ?>

    <div class="content">

        <?php render_topbar(); ?>

        <!-- MAIN SECTION -->
        <main class="room-main">

            <a href="facilities.php" class="go-back">
                <?php echo icon($icons['arrow-left']); ?> Go Back
            </a>

            <?php if ($notice !== ''): ?>
                <div class="notice notice-success" role="status">
                    <?php echo icon($icons['check']); ?> <?php echo htmlspecialchars($notice); ?>
                </div>
            <?php endif; ?>

            <!-- Room header + actions -->
            <div class="room-head">
                <div class="room-title">
                    <span class="room-title-icon"><?php echo icon($icons[$icon_key]); ?></span>
                    <span class="room-title-text">
                        <strong><?php echo htmlspecialchars($room['room_name']); ?></strong>
                        <small><?php echo htmlspecialchars($room['room_tag'] ?: 'No tag'); ?></small>
                    </span>
                </div>

                <div class="search-box">
                    <input type="text" id="equipmentSearch" placeholder="Filter equipment..." autocomplete="off" aria-label="Filter equipment">
                    <?php echo icon($icons['search']); ?>
                </div>

                <div class="room-actions">
                    <a href="equipment_form.php?room_id=<?php echo $room_id; ?>" class="btn btn-gray">
                        <?php echo icon($icons['plus']); ?> New Equipment
                    </a>

                    <form method="POST" action="delete_room.php">
                        <input type="hidden" name="id" value="<?php echo $room_id; ?>">
                        <button type="submit" class="btn btn-red"
                                data-confirm-title="Delete this room?"
                                data-confirm="This will permanently delete &quot;<?php echo htmlspecialchars($room['room_name']); ?>&quot; and all of its equipment. This cannot be undone."
                                data-confirm-ok="Delete Room">Delete Room</button>
                    </form>

                    <a href="add_room.php?id=<?php echo $room_id; ?>" class="btn btn-gray">Edit Room</a>
                </div>
            </div>

            <!-- Equipment table -->
            <div class="table-wrap">
                <table class="equipment-table">
                    <thead>
                        <tr>
                            <th>Equipment Id</th>
                            <th>Equipment Name</th>
                            <th>Status</th>
                            <th class="col-desc">Description / Comments</th>
                            <th>Date Reported</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="equipmentBody">
                        <?php foreach ($equipment as $item): ?>
                            <?php
                            $status_class = strtolower($item['status']);
                            $reported = $item['updated_at'] ?: $item['created_at'];
                            $search_text = strtolower($item['id'] . ' ' . $item['equipment_name'] . ' ' . $item['status'] . ' ' . ($item['description'] ?? ''));
                            ?>
                            <tr class="eq-row" data-search="<?php echo htmlspecialchars($search_text); ?>">
                                <td><?php echo (int)$item['id']; ?></td>
                                <td class="eq-name"><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                <td><span class="status status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                                <td class="col-desc">
                                    <?php if (trim((string)$item['description']) !== ''): ?>
                                        <span class="desc"><?php echo htmlspecialchars($item['description']); ?></span>
                                    <?php else: ?>
                                        <span class="desc-empty">No description</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($reported)); ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="equipment_form.php?room_id=<?php echo $room_id; ?>&amp;id=<?php echo (int)$item['id']; ?>" class="btn btn-gray btn-block">Update Report</a>
                                        <form method="POST" action="delete_equipment.php">
                                            <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                            <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                                            <button type="submit" class="btn btn-red btn-block"
                                                    data-confirm-title="Delete this report?"
                                                    data-confirm="Delete &quot;<?php echo htmlspecialchars($item['equipment_name']); ?>&quot; (ID <?php echo (int)$item['id']; ?>) from this room? This cannot be undone."
                                                    data-confirm-ok="Delete Report">Delete Report</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (count($equipment) === 0): ?>
                    <p class="empty-message">No equipment in this room yet. Click <strong>New Equipment</strong> to add one.</p>
                <?php endif; ?>
                <p class="empty-message" id="noResults" hidden>No equipment matches your filter.</p>
            </div>

            <nav class="pagination" id="pagination" aria-label="Equipment pages" hidden></nav>

        </main>
    </div>

    <script src="layout.js"></script>
    <script>
    (function () {
        const PER_PAGE = 5;

        const rows = Array.from(document.querySelectorAll('.eq-row'));
        if (!rows.length) return;

        const searchInput = document.getElementById('equipmentSearch');
        const pagination = document.getElementById('pagination');
        const noResults = document.getElementById('noResults');
        const state = { query: '', page: 1 };

        const ARROW_LEFT  = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';
        const ARROW_RIGHT = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';

        function render() {
            const q = state.query.trim().toLowerCase();

            // Filter runs across ALL equipment, not just the visible page
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
