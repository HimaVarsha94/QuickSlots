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
  $var = "[[" . $_POST['add_slot_1_day'] . "," . $_POST['add_slot_1_range'] . "]";  
  if($_POST['add_slot_2_day']!='') {
    $var = $var . ",[" . $_POST['add_slot_2_day'] . "," . $_POST['add_slot_2_range'] . "]";
  }
  if($_POST['add_slot_3_day']!='') {
    $var = $var . ",[" . $_POST['add_slot_3_day'] . "," . $_POST['add_slot_3_range'] . "]";
  }
  $var = $var . "]";

  try{
    $query = $db->prepare('INSERT INTO slot_groups(id,slots,lab,tod) values (?,?,?,?)');
    $query->execute([$_POST['add_slot_id'],$var, $_POST['add_lab'], $_POST['add_tod']]);
    postResponse("addOpt","Slot Group Added.",[$_POST['add_slot_id'],$var, $_POST['add_lab'], $_POST['add_tod']]);    
  }
  catch(PDOException $e)
  {
    if($e->errorInfo[0]==23000)
      postResponse("error","Slot Group already exists.");
    else
      postResponse("error",$e->errorInfo[2]);
  }
  
}
if(valueCheck('action','edit'))
{
  $var = "[[" . $_POST['edit_slot_1_day'] . "," . $_POST['edit_slot_1_range'] . "]";  
  if($_POST['edit_slot_2_day']!='') {
    $var = $var . ",[" . $_POST['edit_slot_2_day'] . "," . $_POST['edit_slot_2_range'] . "]";
  }
  if($_POST['edit_slot_3_day']!='') {
    $var = $var . ",[" . $_POST['edit_slot_3_day'] . "," . $_POST['edit_slot_3_range'] . "]";
  }
  $var = $var . "]";

  try{
    $query = $db->prepare('UPDATE slot_groups SET slots = ?, lab = ?, tod = ? WHERE id = ?');
    $query->execute([$var, $_POST['edit_lab'], $_POST['edit_tod'], $_POST['edit_slot_id']]);
    postResponse("addOpt","Slot Group updated.",[$_POST['edit_slot_id'],$var, $_POST['edit_lab'], $_POST['edit_tod']]);    
  }
  catch(PDOException $e)
  {
    postResponse("error",$e->errorInfo[2]);
  }
  
}
elseif(valueCheck('action','delete'))
{
  $query = $db->prepare('DELETE FROM slot_groups where id = ?');
  $query->execute([$_POST['del_slot_id']]);
  postResponse("removeOpt","Slot Group deleted.");
}
?>
