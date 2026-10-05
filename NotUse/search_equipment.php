<?php

function searchEquipment($conn, $search) {
    $search = "%$search%";

    $stmt = $conn->prepare("
        SELECT equipment.id, equipment.equipment_name, equipment.status,
               equipment.created_at, rooms.room_name, rooms.building
        FROM equipment
        JOIN rooms ON equipment.room_id = rooms.id
        WHERE equipment.equipment_name LIKE ?
           OR rooms.room_name LIKE ?
           OR rooms.building LIKE ?
        ORDER BY equipment.id DESC
    ");

    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();

    return $stmt->get_result();
}
?>
