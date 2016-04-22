<?php

/**
 * Back end routines to add/delete rooms, invoked by dean.php
 * @author Avin E.M; Kunal Dahiya
 */

require_once('functions.php');
require_once('connect_db.php');
if(!sessionCheck('level','dean'))
  die();
rangeCheck('room_name',2,25);
if(valueCheck('action','add'))
{
  rangeCheck('capacity',1,3);
  if(empty($_POST['isLab']))
    $_POST['isLab'] = 0;
  try
  {
    $query = $db->prepare('INSERT INTO rooms(room_name,capacity,lab) values (?,?,?)');
    $query->execute([$_POST['room_name'],$_POST['capacity'],$_POST['isLab']]);
    postResponse("addOpt","Room Added",[$_POST['room_name'],$_POST['capacity'],$_POST['isLab']]);
  }
  catch(PDOException $e)
  {
    if($e->errorInfo[0]==23000)
      postResponse("error","Room already exists");
    else
      postResponse("error",$e->errorInfo[2]);
  }

}
elseif(valueCheck('action', 'update'))
{
  if(empty($_POST['lab']))
    $_POST['lab'] = 0;
  $query = $db->prepare('UPDATE rooms SET capacity=:capacity, lab=:lab where room_name=:room_name');
  $query->execute(array(':capacity' => $_POST['capacity'], ':room_name' => $_POST['room_name'], ':lab' => $_POST['updateLab']));
  // postResponse("updateOpt", "Room Updated", [$_POST['room_name']])
}

elseif(valueCheck('action','delete'))
{
  $query = $db->prepare('DELETE FROM rooms where room_name = ?');
  $query->execute([$_POST['room_name']]);
  postResponse("removeOpt","Room deleted");
}
?>
