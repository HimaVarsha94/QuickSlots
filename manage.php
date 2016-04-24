<?php

/**
 * Restricted to dean level users, provides interface and back end routines to manage departments, faculty, batches and rooms
 * @author Avin E.M; Kunal Dahiya
 */

require_once('functions.php');
if(!sessionCheck('level','dean'))
{
    header("Location: ./login.php");
    die();
}
require_once ('connect_db.php');
?>
<!DOCTYPE HTML>
<html>
<head>
  <title>QuickSlots</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" type="text/css" href="css/styles.css">
  <link rel="stylesheet" type="text/css" href="css/dashboard.css">
  <link rel="stylesheet" type="text/css" href="css/chosen.css">
  <script type="text/javascript" src="js/jquery.min.js" ></script>
  <script type="text/javascript" src="js/form.js"></script>
  <script type="text/javascript" src="js/chosen.js"></script>
  <script>
  $(function()
  {
      $("#main_menu a").each(function() {
          if($(this).prop('href') == window.location.href || window.location.href.search($(this).prop('href'))>-1)
          {
              $(this).parent().addClass('current');
              document.title+= " | " + this.innerHTML;
              $("#shadowhead").html(this.innerHTML);
              return false;
          }
      })
      $("select").chosen();
      $("#fac_level").change(function(){
        $("input[value="+ $("option:selected",this).attr('class') +"]",this.parentNode).attr('checked','checked');
      })

      $('#topmenu').click(function() {
        $('#nav_bar').toggle();
      });
  })
  </script>
</head>

<body style="white-space:nowrap">
  <div id="header">
    <div id="account_info">
      <div class="infoTab"><div class="fixer"></div><div class="dashIcon usr"></div><div id="fName"><?=$_SESSION['fName']?></div></div>
      <div class="infoTab"><div class="fixer"></div><a href="logout.php" id="logout"><div class="dashIcon logout"></div><div>Logout</div></a></div>
    </div>
    <div id="header_text" style="box-sizing:border-box;padding:5px;">
      <img id="topmenu" src="images/information.png" style="height:30px;width:auto;float:left;margin-top:3px;margin-left:15px;cursor:pointer;"></img>
      <p style="float:left;margin-top:-5px;margin-left:15px;">QuickSlots</p>
    </div>
  </div>
  <div id="shadowhead"></div>
  <div id="nav_bar">
    <ul class="main_menu" id="main_menu">
      <li class="limenu"><a href="dean.php">Manage Timetables</a></li>
      <li class="limenu"><a href="manage.php?action=departments">Manage Departments</a></li>
      <li class="limenu"><a href="manage.php?action=faculty">Manage Faculty</a></li>
      <li class="limenu"><a href="manage.php?action=batches">Manage Batches</a></li>
      <li class="limenu"><a href="manage.php?action=rooms">Manage Rooms</a></li>
      <li class="limenu"><a href="manage.php?action=slot_groups">Manage Slot Groups</a></li>
      <li class="limenu"><a href="faculty.php">Manage Courses</a></li>
      <li class="limenu"><a href="addpreference.php">Add Preferences</a></li>
      <li class="limenu"><a href="allocate.php">Allocate Timetable</a></li>
      <li class="limenu"><a href="./">View Timetable</a></li>
    </ul>
  </div>
  <div id="content">
  <?php if(valueCheck('action','faculty')) : ?>
    <div class="box">
      <div class="boxbg"></div>
      <div class="avatar"><div class="icon add"></div></div>
      <div class="title">Add Faculty</div>
      <div class="elements">
        <form method="post" action="register.php">
          <input type="text" name="fullName" class="styled uInfo" required pattern=".{6,50}" title="6 to 50 characters" placeholder="Full Name" />
          <input type="text" name="uName" class="styled username" required pattern="[^ ]{4,25}" title="4 to 25 characters without spaces" placeholder="Username" />
          <select  name="dept" class="stretch" data-placeholder="Choose Department..." required>
            <option label="Choose Department..."></option>
            <?php
            foreach($db->query('SELECT * FROM depts') as $dept)
              echo "<option value=\"{$dept['dept_code']}\">{$dept['dept_name']} ({$dept['dept_code']})</option>";
            ?>
          </select>
          <input  type="password" name="pswd" class="styled pwd" required pattern="[^ ]{8,32}" title="8 to 32 characters without spaces" placeholder="Password" />
          <input type="password" class="styled pwd" required pattern="[^ ]{8,32}" title="8 to 32 characters without spaces" placeholder="Confirm password" />
          <div style="text-align: justify;height: 18px">
            <div class="inline">
              <input type="radio" class="styled" name="level" id="addFaculty" value="faculty" checked><label for="addFaculty">Faculty</label>
            </div>
            <div class="inline">
              <input type="radio" class="styled" name="level" id="addHOD" value="hod"><label for="addHOD">HOD</label>
            </div>
            <div class="inline">
              <input type="radio" class="styled" name="level" id="addDean" value="dean"><label for="addDean">Dean</label>
            </div>
            <span class="inline stretch"></span>
          </div>
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Register</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="avatar"><div class="icon key"></div></div>
      <div class="title">Change Faculty Access</div>
      <div class="elements">
        <form method="post" action="register.php?action=changeLevel" >
          <select name="uName" id="fac_level" class="updateSelect stretch" data-placeholder="Choose Faculty..." required>
            <option label="Choose Faculty..."></option>
            <?php
            foreach($db->query('SELECT * FROM faculty') as $fac)
              echo "<option value=\"{$fac['uName']}\" class=\"{$fac['level']}\">{$fac['fac_name']} ({$fac['uName']})</option>"
            ?>
          </select>
          <div style="text-align: justify;height: 18px">
            <div class="inline">
              <input type="radio" class="styled" name="level" id="changeFaculty" value="faculty"><label for="changeFaculty">Faculty</label>
            </div>
            <div class="inline">
              <input type="radio" class="styled" name="level" id="changeHOD" value="hod"><label for="changeHOD">HOD</label>
            </div>
            <div class="inline">
              <input type="radio" class="styled" name="level" id="changeDean" value="dean"><label for="changeDean">Dean</label>
            </div>
            <span class="inline stretch"></span>
          </div>
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Change</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="avatar"><div class="icon remove"></div></div>
      <div class="title">Delete Faculty</div>
      <div class="elements">
        <form method="post" action="register.php?action=deleteFaculty" class="confirm">
          <select name="uName" class="updateSelect stretch" data-placeholder="Choose Faculty..." required>
            <option label="Choose Faculty..."></option>
            <?php
            foreach($db->query('SELECT * FROM faculty') as $fac)
              echo "<option value=\"{$fac['uName']}\">{$fac['fac_name']} ({$fac['uName']})</option>"
            ?>
          </select>
          <input type="hidden" id="confirm_msg" value="Are you sure you want to delete the selected faculty?">
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Delete</button>
          </div>
        </form>
      </div>
    </div>
  <?php elseif(valueCheck('action','slot_groups')) : ?>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon add"></div></div>
      <div class="title">Add Slot Group</div>
      <div class="elements">
        <form method="post" action="slot_groups.php?action=add" onsubmit="setTimeout(function () { window.location.reload(); }, 10)">
          <input type="text" name="add_slot_id" class="styled uInfo" placeholder="Slot Group ID" required/>
          <div>
            <div>
              <select name="add_slot_1_day" class="updateSelect stretch" data-placeholder="Choose Slot 1 Day..." required>
                <option label="Choose Slot 1 Day..."></option>
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thrusday</option>
                <option value="5">Friday</option>
              </select>
            </div>
            <div>
              <select name="add_slot_1_range" class="updateSelect stretch" data-placeholder="Choose Slot 1 Range..." required>
                <option label="Choose Slot 1 Range..."></option>
                <option value="0900,0955">09:00 - 09:55</option>
                <option value="1000,1055">10:00 - 10:55</option>
                <option value="1100,1155">11:00 - 11:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1430,1555">14:30 - 15:55</option>
                <option value="1600,1725">16:00 - 17:25</option>
                <option value="1730,1900">17:30 - 19:00</option>
                <option value="1900,2030">19:00 - 20:30</option>
                <option value="1430,1725">14:30 - 17:25</option>
                <option value="0900,1155">09:00 - 11:55</option>
              </select>
            </div>
          </div>
          <div>
            <div>
              <select name="add_slot_2_day" class="updateSelect stretch" data-placeholder="Choose Slot 2 Day...">
                <option label="Choose Slot 2 Day..."></option>
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thrusday</option>
                <option value="5">Friday</option>
              </select>
            </div>
            <div>
              <select name="add_slot_2_range" class="updateSelect stretch" data-placeholder="Choose Slot 2 Range...">
                <option label="Choose Slot 1 Range..."></option>
                <option value="0900,0955">09:00 - 09:55</option>
                <option value="1000,1055">10:00 - 10:55</option>
                <option value="1100,1155">11:00 - 11:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1430,1555">14:30 - 15:55</option>
                <option value="1600,1725">16:00 - 17:25</option>
                <option value="1730,1900">17:30 - 19:00</option>
                <option value="1900,2030">19:00 - 20:30</option>
                <option value="1430,1725">14:30 - 17:25</option>
                <option value="0900,1155">09:00 - 11:55</option>
              </select>
            </div>
          </div>
          <div>
            <div>
              <select name="add_slot_3_day" class="updateSelect stretch" data-placeholder="Choose Slot 3 Day...">
                <option label="Choose Slot 3 Day..."></option>
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thrusday</option>
                <option value="5">Friday</option>
              </select>
            </div>
            <div>
              <select name="add_slot_3_range" class="updateSelect stretch" data-placeholder="Choose Slot 3 Range...">
                <option label="Choose Slot 1 Range..."></option>
                <option value="0900,0955">09:00 - 09:55</option>
                <option value="1000,1055">10:00 - 10:55</option>
                <option value="1100,1155">11:00 - 11:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1430,1555">14:30 - 15:55</option>
                <option value="1600,1725">16:00 - 17:25</option>
                <option value="1730,1900">17:30 - 19:00</option>
                <option value="1900,2030">19:00 - 20:30</option>
                <option value="1430,1725">14:30 - 17:25</option>
                <option value="0900,1155">09:00 - 11:55</option>
              </select>
            </div>
          </div>
          <div style="text-align: justify;height: 18px">
            <div class="inline">
              <input type="radio" class="styled" name="add_tod" id = "morning" value="morning" checked><label for="morning">Morning</label>
            </div>
            <div class="inline">
              <input type="radio" class="styled" name="add_tod" id = "evening" value="evening"><label for="evening">Evening</label>
            </div>
            <span class="inline stretch"></span>
          </div>
          <div>
            <input type='hidden' value='0' name='add_lab'>
            <input type="checkbox" name="add_lab" value="1">Lab
          </div>
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Register</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon key"></div></div>
      <div class="title">Edit Slot Group</div>
      <div class="elements">
        <form method="post" action="slot_groups.php?action=edit" onsubmit="setTimeout(function () { window.location.reload(); }, 10)">
          <select name="edit_slot_id" class="updateSelect stretch" data-placeholder="Choose Slot Group..." required>
            <option label="Choose Slot Group..."></option>
            <?php
            foreach($db->query('SELECT * FROM slot_groups') as $slot)
              echo "<option value=\"{$slot['id']}\">{$slot['id']} </option>"
            ?>
          </select>
          <div>
            <div>
              <select name="edit_slot_1_day" class="updateSelect stretch" data-placeholder="Choose Slot 1 Day..." required>
                <option label="Choose Slot 1 Day..."></option>
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thrusday</option>
                <option value="5">Friday</option>
              </select>
            </div>
            <div>
              <select name="edit_slot_1_range" class="updateSelect stretch" data-placeholder="Choose Slot 1 Range..." required>
                <option label="Choose Slot 1 Range..."></option>
                <option value="0900,0955">09:00 - 09:55</option>
                <option value="1000,1055">10:00 - 10:55</option>
                <option value="1100,1155">11:00 - 11:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1430,1555">14:30 - 15:55</option>
                <option value="1600,1725">16:00 - 17:25</option>
                <option value="1730,1900">17:30 - 19:00</option>
                <option value="1900,2030">19:00 - 20:30</option>
                <option value="1430,1725">14:30 - 17:25</option>
                <option value="0900,1155">09:00 - 11:55</option>
              </select>
            </div>
          </div>
          <div>
            <div>
              <select name="edit_slot_2_day" class="updateSelect stretch" data-placeholder="Choose Slot 2 Day...">
                <option label="Choose Slot 2 Day..."></option>
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thrusday</option>
                <option value="5">Friday</option>
              </select>
            </div>
            <div>
              <select name="edit_slot_2_range" class="updateSelect stretch" data-placeholder="Choose Slot 2 Range...">
                <option label="Choose Slot 1 Range..."></option>
                <option value="0900,0955">09:00 - 09:55</option>
                <option value="1000,1055">10:00 - 10:55</option>
                <option value="1100,1155">11:00 - 11:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1430,1555">14:30 - 15:55</option>
                <option value="1600,1725">16:00 - 17:25</option>
                <option value="1730,1900">17:30 - 19:00</option>
                <option value="1900,2030">19:00 - 20:30</option>
                <option value="1430,1725">14:30 - 17:25</option>
                <option value="0900,1155">09:00 - 11:55</option>
              </select>
            </div>
          </div>
          <div>
            <div>
              <select name="edit_slot_3_day" class="updateSelect stretch" data-placeholder="Choose Slot 3 Day...">
                <option label="Choose Slot 3 Day..."></option>
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thrusday</option>
                <option value="5">Friday</option>
              </select>
            </div>
            <div>
              <select name="edit_slot_3_range" class="updateSelect stretch" data-placeholder="Choose Slot 3 Range...">
                <option label="Choose Slot 1 Range..."></option>
                <option value="0900,0955">09:00 - 09:55</option>
                <option value="1000,1055">10:00 - 10:55</option>
                <option value="1100,1155">11:00 - 11:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1200,1255">12:00 - 12:55</option>
                <option value="1430,1555">14:30 - 15:55</option>
                <option value="1600,1725">16:00 - 17:25</option>
                <option value="1730,1900">17:30 - 19:00</option>
                <option value="1900,2030">19:00 - 20:30</option>
                <option value="1430,1725">14:30 - 17:25</option>
                <option value="0900,1155">09:00 - 11:55</option>
              </select>
            </div>
          </div>
          <div style="text-align: justify;height: 18px">
            <div class="inline">
              <input type="radio" class="styled" name="edit_tod" id = "edit_morning" value="morning" checked><label for="edit_morning">Morning</label>
            </div>
            <div class="inline">
              <input type="radio" class="styled" name="edit_tod" id = "edit_evening" value="evening"><label for="edit_evening">Evening</label>
            </div>
            <span class="inline stretch"></span>
          </div>
          <div>
            <input type='hidden' value='0' name='edit_lab'>
            <input type="checkbox" name="edit_lab" value="1">Lab
          </div>
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Change</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon remove"></div></div>
      <div class="title">Delete Slot Group</div>
      <div class="elements">
        <form method="post" action="slot_groups.php?action=delete" class="confirm" onsubmit="setTimeout(function () { window.location.reload(); }, 10)">
          <select name="del_slot_id" class="updateSelect stretch" data-placeholder="Choose Slot Group..." required>
            <option label="Choose Slot Group..."></option>
            <?php
            foreach($db->query('SELECT * FROM slot_groups') as $slot)
              echo "<option value=\"{$slot['id']}\">{$slot['id']} </option>"
            ?>
          </select>
          <input type="hidden" id="confirm_msg" value="Are you sure you want to delete the selected slot group?">
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Delete</button>
          </div>
        </form>
      </div>
    </div>
  <?php elseif(valueCheck('action','departments')) : ?>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon add"></div></div>
      <div class="title">Add Department</div>
      <div class="elements">
        <form method="post" action="depts.php?action=add">
          <input type="text" name="dept_code" class="styled details" required pattern="[^ ]{2,5}" title="2 to 5 characters" placeholder="Department Code" />
          <input type="text" name="dName" class="styled details" required pattern=".{6,50}" title="6 to 50 characters" placeholder="Department Name" />
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Add</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon remove"></div></div>
      <div class="title">Delete Department</div>
      <div class="elements">
        <form method="post" action="depts.php?action=delete">
          <select name="dept_code" class="updateSelect stretch"  data-placeholder="Choose Department..." required>
            <option label="Choose Department..."></option>
            <?php
            foreach($db->query('SELECT * FROM depts') as $dept)
              echo "<option value=\"{$dept['dept_code']}\">{$dept['dept_name']} ({$dept['dept_code']})</option>";
            ?>
          </select>
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Delete</button>
          </div>
        </form>
      </div>
    </div>
  <?php elseif(valueCheck('action','batches')) : ?>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon add"></div></div>
      <div class="title">Add Batch</div>
      <div class="elements">
        <form method="post" action="batches.php?action=add">
          <input type="text" name="batch_name" class="styled uInfo" required pattern="[^:]{2,30}" title="2 to 30 alphanumeric characters" placeholder="Batch Name" />
          <select name="dept" class="stretch" data-placeholder="Choose Department..." required>
            <option label="Choose Department..."></option>
            <?php
            foreach($db->query('SELECT * FROM depts') as $dept)
              echo "<option value=\"{$dept['dept_code']}\">{$dept['dept_name']} ({$dept['dept_code']})</option>";
            ?>
          </select>
          <input type="text" name="size" class="styled details" required pattern="[0-9]{1,3}" title="Number less than 1000, this will be used to suggest rooms" placeholder="Batch Size" />
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Add</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon remove"></div></div>
      <div class="title">Delete Batch</div>
      <div class="elements">
        <form method="post" action="batches.php?action=delete" class="confirm">
          <select name="batch" class="updateSelect stretch"  data-placeholder="Choose Batch..." required>
            <option label="Choose Batch..."></option>
            <?php
            foreach($db->query('SELECT * FROM batches') as $batch)
              echo "<option value=\"{$batch['batch_name']} : {$batch['batch_dept']}\">{$batch['batch_name']} : {$batch['batch_dept']} ({$batch['size']})</option>";
            ?>
          </select>
          <input type="hidden" id="confirm_msg" value="Are you sure you want to delete the selected batch?">
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Delete</button>
          </div>
        </form>
      </div>
    </div>
  <?php else: ?>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon add"></div></div>
      <div class="title">Add Room</div>
      <div class="elements">
        <form method="post" action="rooms.php?action=add">
          <input type="text" name="room_name" class="styled details" required pattern="[^:]{2,25}" title="2 to 25 alphanumeric characters" placeholder="Room Name" />
          <input type="text" name="capacity" class="styled details" required pattern="[0-9]{1,3}" title="Number less than 1000" placeholder="Capacity" />
          <div class="center">
            <input type="checkbox" class="styled" id="isLab" value="1" name="isLab">
            <label for="isLab">Lab</label>
          </div>
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Add</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon remove"></div></div>
      <div class="title">Update Room</div>
      <div class="elements">
        <form method="post" action="rooms.php?action=update">
          <select name="room_name" class="updateSelect stretch"  data-placeholder="Choose Room..." required>
            <option label="Choose Room..."></option>
            <?php
            foreach($db->query('SELECT * FROM rooms') as $room)
              echo "<option value=\"{$room['room_name']}\">{$room['room_name']} ({$room['capacity']})</option>";
            ?>
          </select>
          <input type="text" name="capacity" class="styled details" required pattern="[0-9]{1,3}" title="Number less than 1000" placeholder="Enter New Capacity" />
          <div class="center">
            <input type="checkbox" class="styled" id="updateLab" value="1" name="updateLab">
            <label for="updateLab">Lab</label>
          </div>
          <!-- <input type="text" name="capacity" class="styled details" required pattern="[0-9]{1,3}" title="Number less than 1000" placeholder="Enter New Capacity" /> -->
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Update</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon remove"></div></div>
      <div class="title">Delete Room</div>
      <div class="elements">
        <form method="post" action="rooms.php?action=delete" class="confirm">
          <select name="room_name" class="updateSelect stretch"  data-placeholder="Choose Room..." required>
            <option label="Choose Room..."></option>
            <?php
            foreach($db->query('SELECT * FROM rooms') as $room)
              echo "<option value=\"{$room['room_name']}\">{$room['room_name']} ({$room['capacity']})</option>";
            ?>
          </select>
          <div class="blocktext info"></div>
          <input type="hidden" id="confirm_msg" value="Are you sure you want to delete the selected room?">
          <div class="center button">
            <button>Delete</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
  </div>
</body>
</html>
