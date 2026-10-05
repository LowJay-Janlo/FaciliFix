<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Floor tags used by the filter buttons (must match the values stored in rooms.room_tag)
$floor_tags = ['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];

// Inline SVG icon paths
$icons = [
    'menu'       => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
    'logout'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    'arrow-left' => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
    'arrow-right'=> '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
    'search'     => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    'plus'       => '<path d="M5 12h14"/><path d="M12 5v14"/>',
    'book-open'  => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
    'book'       => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>',
    'briefcase'  => '<rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
];

function icon($paths) {
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">' . $paths . '</svg>';
}

// Pick a card icon based on the room name
function room_icon_key($name) {
    $n = strtolower($name);
    if (strpos($n, 'library') !== false) return 'book-open';
    if (strpos($n, 'office') !== false || strpos($n, 'registrar') !== false) return 'briefcase';
    return 'book';
}

$result = $conn->query("SELECT id, room_name, room_tag FROM rooms ORDER BY id DESC");
$rooms = [];
while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities - FaciliFix</title>
    <link href="facilities.css" rel="stylesheet">
</head>
<body>

    <!-- Eto Yung SideBar For Quick Naavigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <img src="Assets/Images/logo.png" alt="FaciliFix Logo" draggable="false">
        </div>
        <nav class="sidebar-nav" aria-label="Main navigation">
            <a href="facilities.php" class="active">Facilities</a>
            <a href="admin_reports.php">New Report</a>
            <a href="manage_staff.php">Staff Account</a>
        </nav>
    </aside>

    <div class="content">

        <!-- Hamburger/Logout Sa Nav -->
        <header class="topbar">
            <button type="button" class="icon-button" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="true">
                <?php echo icon($icons['menu']); ?>
            </button>
            <a href="logout.php" class="logout-button">
                <?php echo icon($icons['logout']); ?> Log Out
            </a>
        </header>

        <!-- The main Content -->
        <main class="facilities-main">

            <div class="facilities-head">
                <h1>Facilities</h1>
                <a href="admin_dashboard.php" class="go-back">
                    <?php echo icon($icons['arrow-left']); ?> Go Back
                </a>
            </div>

            <div class="toolbar">
                <div class="search-box">
                    <input type="text" id="roomSearch" placeholder="Filter rooms by name..." autocomplete="off" aria-label="Search rooms by name">
                    <?php echo icon($icons['search']); ?>
                </div>
                <a href="add_room.php" class="add-room-button">
                    <?php echo icon($icons['plus']); ?> Add Room
                </a>
            </div>

            <div class="tag-filters" role="group" aria-label="Filter by floor">
                <?php foreach ($floor_tags as $tag): ?>
                    <button type="button" class="tag-chip" data-tag="<?php echo htmlspecialchars($tag); ?>" aria-pressed="false">
                        <?php echo htmlspecialchars($tag); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php if (count($rooms) > 0): ?>

                <section class="room-grid" id="roomGrid" aria-label="Rooms">
                    <?php foreach ($rooms as $room): ?>
                        <?php $tag_label = $room['room_tag'] ?: 'No tag'; ?>
                        <!-- Card link is a placeholder until the room page is built -->
                        <a href="#" class="room-card"
                           data-name="<?php echo htmlspecialchars($room['room_name']); ?>"
                           data-tag="<?php echo htmlspecialchars($room['room_tag'] ?? ''); ?>">
                            <span class="room-icon">
                                <?php echo icon($icons[room_icon_key($room['room_name'])]); ?>
                            </span>
                            <span class="room-info">
                                <h2><?php echo htmlspecialchars($room['room_name']); ?></h2>
                                <p><?php echo htmlspecialchars($tag_label); ?></p>
                            </span>
                            <span class="room-arrow">
                                <?php echo icon($icons['arrow-right']); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </section>

                <p class="empty-message" id="noResults" hidden>No rooms match your search.</p>

                <nav class="pagination" id="pagination" aria-label="Room pages" hidden></nav>

            <?php else: ?>
                <p class="empty-message">No rooms yet. Click <strong>Add Room</strong> to create one.</p>
            <?php endif; ?>

        </main>
    </div>

    <script>
    (function () {
        const PER_PAGE = 8;
        const body = document.body;

        // ---------- Sidebar show / hide ----------
        const toggle = document.getElementById('sidebarToggle');
        if (window.innerWidth < 800) body.classList.add('sidebar-collapsed');

        toggle.addEventListener('click', function () {
            const collapsed = body.classList.toggle('sidebar-collapsed');
            toggle.setAttribute('aria-expanded', String(!collapsed));
        });

        // ---------- Search + tag filter + pagination ----------
        const cards = Array.from(document.querySelectorAll('.room-card'));
        if (!cards.length) return;

        const searchInput = document.getElementById('roomSearch');
        const chips = Array.from(document.querySelectorAll('.tag-chip'));
        const pagination = document.getElementById('pagination');
        const noResults = document.getElementById('noResults');
        const state = { query: '', tag: '', page: 1 };

        const ARROW_LEFT  = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';
        const ARROW_RIGHT = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';

        function render() {
            const q = state.query.trim().toLowerCase();

            // Search runs across ALL rooms, not just the visible page
            const filtered = cards.filter(function (card) {
                const nameMatch = !q || card.dataset.name.toLowerCase().includes(q);
                const tagMatch = !state.tag || card.dataset.tag === state.tag;
                return nameMatch && tagMatch;
            });

            const pages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
            state.page = Math.min(state.page, pages);
            const start = (state.page - 1) * PER_PAGE;

            cards.forEach(function (card) { card.hidden = true; });
            filtered.slice(start, start + PER_PAGE).forEach(function (card) { card.hidden = false; });

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

        // Click a tag to filter; click it again to clear the filter
        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                state.tag = (state.tag === chip.dataset.tag) ? '' : chip.dataset.tag;
                chips.forEach(function (c) {
                    c.setAttribute('aria-pressed', String(c.dataset.tag === state.tag));
                });
                state.page = 1;
                render();
            });
        });

        render();
    })();
    </script>

</body>
</html>