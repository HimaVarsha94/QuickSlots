<?php

require_once('functions.php');
require_once('connect_db.php');

// echo()

// echo '{"course_id": "'.$_POST["course_id"].'", "Slot1" : "'.$_POST["course_slot1"].'",  "Slot2" : "'.$_POST["course_slot2"].'", "Slot3" : "'.$_POST["course_slot3"].'"}';

if(valueCheck('action','del'))
{
    $query = $db->prepare('DELETE FROM preferences WHERE id = ?');
    $query->execute([$_POST['preference_id']]);
    postResponse("addOpt","Slot Group Deleted.",[$_POST['preference_id']]);
}
else {
	$query = $db -> prepare('INSERT INTO preferences(course_id, slot_group) VALUES (?,?) ');
	if(isset($_POST["course_slot1"]) && $_POST["course_slot1"] != "select_slot")
	    $query -> execute([$_POST["course_id"], $_POST["course_slot1"]]);
	if(isset($_POST["course_slot2"]) && $_POST["course_slot2"] != "select_slot")
	    $query -> execute([$_POST["course_id"], $_POST["course_slot2"]]);
	if(isset($_POST["course_slot3"]) && $_POST["course_slot3"] != "select_slot")
	    $query -> execute([$_POST["course_id"], $_POST["course_slot3"]]);
        postResponse("addOpt","Preferences Added",[]);
}
 ?>
