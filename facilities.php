<?php
session_start();
include 'db.php';
include 'partials.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT id, room_name, room_tag, room_icon FROM rooms ORDER BY id DESC");
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
    <link href="modal.css" rel="stylesheet">
</head>
<body>

    <?php render_sidebar('facilities'); ?>

    <div class="content">

        <?php render_topbar(); ?>

        <!-- MAIN SECTION -->
        <main class="facilities-main">

            <?php if (isset($_GET['added'])): ?>
                <div class="notice notice-success" role="status">
                    <?php echo icon($icons['check']); ?> Room added successfully!
                </div>
            <?php endif; ?>

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
                        <?php
                        $tag_label = $room['room_tag'] ?: 'No tag';
                        $icon_key  = $room_icon_options[$room['room_icon']]['icon'] ?? 'graduation';
                        ?>
                        <!-- Card link is a placeholder until the room page is built -->
                        <a href="#" class="room-card"
                           data-name="<?php echo htmlspecialchars($room['room_name']); ?>"
                           data-tag="<?php echo htmlspecialchars($room['room_tag'] ?? ''); ?>">
                            <span class="room-icon">
                                <?php echo icon($icons[$icon_key]); ?>
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

    <script src="layout.js"></script>
    <script>
    (function () {
        const PER_PAGE = 8;

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