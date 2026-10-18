<?php
require 'auth.php';
require_role('Admin');
include 'partials.php';
include 'media.php';


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
    "SELECT id, equipment_name, status, description, image, created_at, updated_at
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
    <link href="css/base.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>

    <?php render_topbar('facilities'); ?>

        <main class="page">

            <?php if ($notice !== ''): ?>
                <div class="notice notice-success" role="status">
                    <?php echo icon($icons['check']); ?> <?php echo htmlspecialchars($notice); ?>
                </div>
            <?php endif; ?>

            <div class="room-head">
                <?php render_back('facilities.php', 'Back to facilities'); ?>

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
                        <?php echo csrf_field(); ?>
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
                            <th>Image</th>
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
                            $status_class = htmlspecialchars(strtolower($item['status']));
                            $reported = $item['updated_at'] ?: $item['created_at'];
                            $search_text = strtolower($item['id'] . ' ' . $item['equipment_name'] . ' ' . $item['status'] . ' ' . ($item['description'] ?? ''));
                            ?>
                            <tr class="eq-row" data-search="<?php echo htmlspecialchars($search_text); ?>">
                                <td><?php echo (int)$item['id']; ?></td>
                                <td>
                                    <?php if ($item['image']): ?>
                                        <a href="<?php echo htmlspecialchars(image_url($item['image'])); ?>" class="thumb" target="_blank" rel="noopener"
                                           aria-label="Open photo of <?php echo htmlspecialchars($item['equipment_name']); ?>">
                                            <img src="<?php echo htmlspecialchars(image_url($item['image'])); ?>" alt="" loading="lazy">
                                        </a>
                                    <?php else: ?>
                                        <span class="thumb thumb-empty" title="No photo"><?php echo icon($icons['image']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="eq-name"><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                <td><span class="status status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                                <td class="col-desc">
                                    <?php if (trim((string)$item['description']) !== ''): ?>
                                        <span class="desc"><?php echo nl2br(htmlspecialchars($item['description'])); ?></span>
                                    <?php else: ?>
                                        <span class="desc-empty">No description</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($reported)); ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="equipment_form.php?room_id=<?php echo $room_id; ?>&amp;id=<?php echo (int)$item['id']; ?>" class="btn btn-gray btn-block">Update Report</a>
                                        <form method="POST" action="delete_equipment.php">
                                            <?php echo csrf_field(); ?>
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

    <script src="js/layout.js"></script>
    <script src="js/room.js"></script>

</body>
</html>
