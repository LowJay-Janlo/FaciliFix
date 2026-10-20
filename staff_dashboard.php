<?php
require 'auth.php';
require_role('Staff');
include 'partials.php';

$result = $conn->query("SELECT id, room_name, room_tag, room_icon FROM rooms ORDER BY room_name ASC");
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
    <title>Rooms - FaciliFix</title>
    <link href="css/base.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>

    <?php render_topbar('rooms', true, 'Staff'); ?>

        <main class="page">

            <div class="page-head">
                <h1>Rooms</h1>
            </div>
            <p class="page-intro">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>. Pick a room to see its equipment and report its condition.</p>
            <hr class="page-divider">

            <div class="toolbar">
                <div class="search-box">
                    <input type="text" id="roomSearch" placeholder="Filter rooms by name or floor..." autocomplete="off" aria-label="Search rooms by name or floor">
                    <?php echo icon($icons['search']); ?>
                </div>
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
                        <a href="room_details.php?room_id=<?php echo (int)$room['id']; ?>" class="room-card"
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
                <p class="empty-message">There are no rooms yet. Please ask an administrator to add them.</p>
            <?php endif; ?>

        </main>

    <script src="js/layout.js"></script>
    <script src="js/facilities.js"></script>

</body>
</html>
