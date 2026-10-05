<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$message='';

if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $room_id=trim($_POST['room_id']);
    $equipment_name=trim($_POST['equipment_name']);
    $status='Working';

    if($room_id && $equipment_name){
        $stmt=$conn->prepare("INSERT INTO equipment (room_id,equipment_name,status) VALUES (?,?,?)");
        $stmt->bind_param("iss",$room_id,$equipment_name,$status);
        $message=$stmt->execute() ? "Equipment added successfully!" : "Error: ".$conn->error;
        $stmt->close();
    }else{
        $message="Please fill in all required fields.";
    }
}

$rooms_result=$conn->query("SELECT * FROM rooms ORDER BY room_name ASC");

$equipment_result=$conn->query("
    SELECT equipment.id,equipment.equipment_name,equipment.status,equipment.created_at,
           rooms.room_name,rooms.building
    FROM equipment
    JOIN rooms ON equipment.room_id=rooms.id
    ORDER BY rooms.room_name ASC,equipment.id DESC
");

$room_equipment=[];

while($row=$equipment_result->fetch_assoc()){
    $room=$row['room_name']." (".$row['building'].")";
    $room_equipment[$room][]=$row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Equipment - FaciliFix</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container my-5">

<a href="admin_dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
<h2>Manage Equipment</h2>

<?php if($message): ?>
<div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>


<!-- ADD EQUIPMENT -->

<div class="card shadow p-4 mb-4">
<h4>Add New Equipment</h4>

<form method="POST" id="equipmentForm">

<div class="mb-3">
<label class="form-label">Select Room Location</label>

<div class="room-box">
<input type="text" id="roomSearch" class="form-control"
placeholder="Choose or search a room..." autocomplete="off">

<input type="hidden" name="room_id" id="roomId">

<div id="roomList" class="list-group room-list">

<?php while($room=$rooms_result->fetch_assoc()): ?>

<button type="button"
class="list-group-item list-group-item-action room-option"
data-id="<?php echo $room['id']; ?>"
data-name="<?php echo htmlspecialchars($room['room_name']." (".$room['building'].")"); ?>">

<?php echo htmlspecialchars($room['room_name']." (".$room['building'].")"); ?>

</button>

<?php endwhile; ?>

</div>
</div>
</div>

<div class="mb-3">
<label class="form-label">Equipment Name / Description</label>

<input type="text" name="equipment_name" class="form-control"
placeholder="e.g., Dell Desktop PC #1 or Epson Projector" required>
</div>

<button type="submit" class="btn btn-success">Add Equipment</button>

</form>
</div>


<!-- EXISTING EQUIPMENT -->

<div class="card shadow p-4">

<h4>PARA LANG SAYO ANG GILING, GILING, GILING KO</h4>

<div class="mb-3">
<input type="text" id="equipmentSearch" class="form-control"
placeholder="Type to search equipment, room, or building..."
autocomplete="off">
</div>

<div id="equipmentList">

<?php foreach($room_equipment as $roomName=>$items): ?>

<div class="equipment-room">

<h5 class="room-title mb-0">
<?php echo htmlspecialchars($roomName); ?>
</h5>

<table class="table table-striped mb-4">

<thead>
<tr>
<th>Equipment Name</th>
<th>Status</th>
<th>Date Added</th>
</tr>
</thead>

<tbody>

<?php foreach($items as $row): ?>

<?php
$badge='bg-success';

if($row['status']=='Broken') $badge='bg-danger';
elseif($row['status']=='Missing') $badge='bg-warning text-dark';

$searchText=strtolower(
    $row['equipment_name']." ".
    $row['status']." ".
    $row['room_name']." ".
    $row['building']
);
?>

<tr class="equipment-row"
data-search="<?php echo htmlspecialchars($searchText); ?>">

<td><?php echo htmlspecialchars($row['equipment_name']); ?></td>

<td>
<span class="badge <?php echo $badge; ?>">
<?php echo htmlspecialchars($row['status']); ?>
</span>
</td>

<td><?php echo htmlspecialchars($row['created_at']); ?></td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

<?php endforeach; ?>

<div id="noResults" class="text-center p-4 no-results">
No equipment found.
</div>

</div>
</div>

</div>


<script>
// ROOM SELECTOR

const roomInput=document.getElementById("roomSearch");
const roomList=document.getElementById("roomList");
const roomId=document.getElementById("roomId");
const roomOptions=document.querySelectorAll(".room-option");

function filterRooms(){
    const q=roomInput.value.toLowerCase();
    let found=false;

    roomOptions.forEach(option=>{
        const show=option.dataset.name.toLowerCase().includes(q);
        option.style.display=show?"block":"none";
        if(show) found=true;
    });

    roomList.style.display=found?"block":"none";
}

roomInput.addEventListener("focus",filterRooms);

roomInput.addEventListener("input",()=>{
    roomId.value="";
    filterRooms();
});

roomOptions.forEach(option=>{
    option.addEventListener("click",()=>{
        roomInput.value=option.dataset.name;
        roomId.value=option.dataset.id;
        roomList.style.display="none";
    });
});

document.addEventListener("click",e=>{
    if(!e.target.closest(".room-box"))
        roomList.style.display="none";
});

document.getElementById("equipmentForm").addEventListener("submit",e=>{
    if(!roomId.value){
        e.preventDefault();
        alert("Please select a room from the list.");
        roomInput.focus();
    }
});


// LIVE EQUIPMENT SEARCH

const search=document.getElementById("equipmentSearch");
const roomGroups=document.querySelectorAll(".equipment-room");
const noResults=document.getElementById("noResults");

search.addEventListener("input",function(){

    const query=this.value.toLowerCase().trim();
    let total=0;

    roomGroups.forEach(group=>{

        const rows=group.querySelectorAll(".equipment-row");
        let matches=0;

        rows.forEach(row=>{

            const text=row.dataset.search;

            if(text.includes(query)){
                row.style.display="";
                matches++;
                total++;
            }else{
                row.style.display="none";
            }

        });

        group.style.display=matches>0?"":"none";
    });

    noResults.style.display=total>0?"none":"block";
});
</script>

</body>
</html>
