<?php

/**
 * Back end routines to add/delete rooms, invoked by dean.php
 * @author Avin E.M; Kunal Dahiya
 */

require_once('functions.php');
require_once('connect_db.php');
if(!sessionCheck('level','dean'))
  die();
if(valueCheck('action','add'))
{
  try{
    $query = $db->prepare('INSERT INTO rooms(room_name,capacity) values (?,?)');
    $query->execute([$_POST['room_name'],$_POST['capacity']]);
    postResponse("addOpt","Room Added",[$_POST['room_name'],$_POST['capacity']]);    
  }
  catch(PDOException $e)
  {
    if($e->errorInfo[0]==23000)
      postResponse("error","Room already exists");
    else
      postResponse("error",$e->errorInfo[2]);
  }
  
}
elseif(valueCheck('action','delete'))
{
  $query = $db->prepare('DELETE FROM slot_groups where id = ?');
  $query->execute([$_POST['slot_id']]);
  postResponse("removeOpt","Slot Group deleted.");
}
?>
