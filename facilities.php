<?php
require 'auth.php';
require_role('Admin');
include 'partials.php';


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
    <link href="css/base.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>

    <?php render_topbar('facilities'); ?>

        <main class="page">

            <?php if (isset($_GET['added'])): ?>
                <div class="notice notice-success" role="status">
                    <?php echo icon($icons['check']); ?> Room added successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="notice notice-success" role="status">
                    <?php echo icon($icons['check']); ?> Room deleted.
                </div>
            <?php endif; ?>

            <div class="page-head">
                <?php render_back('admin_dashboard.php', 'Back to dashboard'); ?>
                <h1>Facilities</h1>
            </div>
            <hr class="page-divider">

            <div class="toolbar">
                <div class="search-box">
                    <input type="text" id="roomSearch" placeholder="Filter rooms by name or floor..." autocomplete="off" aria-label="Search rooms by name or floor">
                    <?php echo icon($icons['search']); ?>
                </div>
                <a href="add_room.php" class="btn btn-gray">
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
                        <a href="room.php?id=<?php echo (int)$room['id']; ?>" class="room-card"
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

    <script src="js/layout.js"></script>
    <script src="js/facilities.js"></script>

</body>
</html>