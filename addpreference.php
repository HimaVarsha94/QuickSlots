<?php

/**
 * Provides interface and back end routines for allocation of courses to time slots
 * @author Avin E.M; Kunal Dahiya
 */

require_once('functions.php');
if(!sessionCheck('logged_in'))
{
  header("Location: ./login.php");
  die();
}
require_once('connect_db.php');
if(!isset($_SESSION['faculty']))
  $_SESSION['faculty'] = $_SESSION['uName'];
if(!sessionCheck('level','faculty'))
{
  if(!empty($_GET['faculty']))
  {
    $query = $db->prepare('SELECT uName FROM faculty where uName = ? AND dept_code=?');
    $query->execute([$_GET['faculty'],$_SESSION['dept']]);
    $fac = $query->fetch();
    if(!empty($fac['uName']))
      $_SESSION['faculty'] = $_GET['faculty'];
  }
}
$query = $db->prepare('SELECT * FROM courses where fac_id = ?');
$query->execute([$_SESSION['faculty']]);
$courses = $query->fetchall();
foreach ($courses as $course) {
  if($course['allow_conflict'] && $current['allowConflicts'])
    continue;
  $blocked[$course['course_id']] = [];
  $filter = !$current['allowConflicts']?"OR allow_conflict=1":"";
  $query = $db->prepare("SELECT course_id,count(*) as batches FROM
      (SELECT * FROM allowed where course_id NOT IN
      (SELECT course_id FROM courses where fac_id = ? $filter)) other NATURAL JOIN
      (SELECT batch_name,batch_dept FROM allowed where course_id=?) batches
       group by course_id");

  $query->execute([$_SESSION['faculty'],$course['course_id']]);

  $conflicts = $query->fetchall();

  foreach ($conflicts as $conflict)
  {
    $query = $db->prepare('SELECT day,slot_num FROM slot_allocs where table_name=? AND course_id=?');
    $query->execute([$current['table_name'],$conflict['course_id']]);
    $conf_slots=$query->fetchall();
    foreach ($conf_slots as $conf_slot)
    {
      $slotStr = $conf_slot['day']. "_" .$conf_slot['slot_num'];
      if(isset($blocked[$course['course_id']][$slotStr]))
          $blocked[$course['course_id']][$slotStr] += $conflict['batches'];
      else
          $blocked[$course['course_id']][$slotStr] = $conflict['batches'];
    }
  }
}

if(valueCheck('action','saveSlots'))
{
  if($current['frozen'])
    postResponse("error","This timetable has been frozen");
  foreach ($_POST as $slotStr => $course_room)
  {
    $course=explode(':', $course_room)[0];
    if(!empty($blocked[$course][$slotStr]))
      postResponse("redirect","allocate.php?error=conflict");
  }
  $query = $db->prepare('DELETE FROM slot_allocs where table_name=? AND course_id IN (SELECT course_id FROM courses where fac_id=?)');
  $query->execute([$current['table_name'],$_SESSION['faculty']]);
  $query = $db->prepare('INSERT INTO slot_allocs values(?,?,?,?,?)');
  try
  {
    foreach ($_POST as $slotStr => $course_room)
    {
      $course_room = explode(':', $course_room);
      $course = $course_room[0];
      $room = $course_room[1];
      $slot = explode('_', $slotStr);
      $query->execute([$current['table_name'],$slot[0],$slot[1],$room,$course]);
    }
  }
  catch(PDOException $e)
  {
    if($e->errorInfo[0]==23000)
      postResponse("error","The selected room has been booked already, rooms list has been refreshed");
    else
      postResponse("error",$e->errorInfo[2]);
  }
  postResponse("info","Slots Saved");
  die();
}
if(valueCheck('action','queryRooms'))
{
    $slot = explode('_', $_POST["slot"]);
    $query = $db->prepare('SELECT min(size) FROM allowed NATURAL JOIN batches where course_id=?');
    $query->execute([$_POST['course']]);
    $minCap = $query->fetch()[0];
    $query = $db->prepare('SELECT room_name,capacity FROM rooms
             where capacity>=? AND room_name NOT IN
             (SELECT room FROM slot_allocs where table_name=? AND day=? AND slot_num=?
              AND course_id NOT IN (SELECT course_id FROM courses where fac_id=?)
              ) ORDER BY capacity');
    $query->execute([$minCap,$current['table_name'],$slot[0],$slot[1],$_SESSION['faculty']]);
    $rooms = $query->fetchall(PDO::FETCH_NUM);
    die(json_encode($rooms));
}
if(valueCheck('action','queryConflict'))
{
    $slot = explode('_', $_POST["slot"]);
    $query = $db->prepare('SELECT course_id,course_name,fac_id,fac_name,GROUP_CONCAT(CONCAT(batch_name,\' : \',batch_dept) ORDER BY batch_name SEPARATOR \', \') as batches from allowed NATURAL JOIN courses NATURAL JOIN (SELECT fac_name,uName as fac_id from faculty) faculty where course_id IN (SELECT course_id from slot_allocs where table_name=? AND day=? AND slot_num=?) AND (batch_name,batch_dept) IN (SELECT batch_name,batch_dept FROM allowed where course_id=?) GROUP BY course_id');
    $query->execute([$current['table_name'],$slot[0],$slot[1],$_POST['course']]);
    $conflicts = $query->fetchall();
    $inf_html = "";
    foreach ($conflicts as $conflict) {
      $fac_info = $conflict['fac_name'];
      if(!sessionCheck('level','faculty'))
        $fac_info = "<a href=\"allocate.php?faculty={$conflict['fac_id']}\">{$conflict['fac_name']}</a>";
      $inf_html .= <<<HTML
      <tr class="data">
          <td class="course_name">{$conflict['course_name']}</td>
          <td class="faculty">$fac_info</td>
          <td class="batch">{$conflict['batches']}</td>
      </tr>
HTML;
  }
  die($inf_html);
}

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
  <link rel="stylesheet" type="text/css" href="css/table.css">
  <script type="text/javascript" src="js/jquery.min.js" ></script>
  <script type="text/javascript" src="js/ui.min.js" ></script>
  <script type="text/javascript" src="js/ui-touch-punch.min.js" ></script>
  <script type="text/javascript" src="js/form.js"></script>
  <script type="text/javascript" src="js/chosen.js"></script>
  <script type="text/javascript" src="js/grid.js"></script>
  <script>

  $(function()
  {
      $("#main_menu a").each(function() {
        if($(this).prop('href') == window.location.href || window.location.href.search($(this).prop('href'))>-1)
        {
          $(this).parent().addClass('current');
          document.title+= " | " + this.innerHTML;
          return false;
        }
      });
      $("select").chosen({allow_single_deselect: true});

    //   $("option[value=''","#courseId").attr('selected','selected');

});

  // $("select").change(function(){
  //   window.location.href='./?'+$("#filters :input[value!='']").serialize();
  // })

  // $(function()
  // {
  //   $("#main_menu a").each(function() {
  //     if($(this).prop('href') == window.location.href || window.location.href.search($(this).prop('href'))>-1)
  //     {
  //         $(this).parent().addClass('current');
  //         document.title+= " | " + this.innerHTML;
  //         return false;
  //     }
  //   });
  //   $("option[value='<?=$current['table_name']?>']","#table_name").attr('selected','selected');
  //   <?php
  //     $t=$current['start_hr'] .":". $current['start_min'] ." ". $current['start_mer'];
  //     echo"drawGrid('{$current['table_name']}',{$current['slots']},{$current['days']},{$current['duration']},'$t');";
  //   ?>
  //   $(".course").draggable({
  //     helper:"clone",
  //     opacity: 0.7,
  //     appendTo: "#rightpane",
  //     tolerance: "fit",
  //     start: function(e,ui)
  //     {
  //       var blocked = $("."+this.id,".blocked");
  //       resetInfo();
  //       $("input",blocked).each(function(){
  //           var cell=$("#"+this.name);
  //               cell.addClass('conflicting');
  //           $.data(cell[0],"content",cell.html());
  //           cell.html(this.value);
  //       })
  //     },
  //     stop: function(){
  //       $(".conflicting").each(function(){
  //           if($(this).hasClass('showInfo'))
  //             return;
  //           if(this.innerHTML)
  //               $(this).html($.data(this,"content"));
  //           $(this).removeClass('conflicting');
  //       });
  //     }
  //   });
  //   $(".cell","#timetable").click(function(){
  //     if(!this.innerHTML || $(this).hasClass('conflicting'))
  //       return false;
  //     $(".selected").removeClass('selected');
  //     $(this).addClass('selected');
  //     resetInfo();
  //     $("#roomSelect").html('<div class="center button"></div>');
  //     $.ajax({
  //       type: "POST",
  //       url: "allocate.php?action=queryRooms",
  //       data: "slot="+this.id+"&course="+$("input[name="+this.id+"]","#courseAlloc").val().split(':')[0],
  //       success: function(result)
  //       {
  //           $("#roomSelect").html('<select name="room_name" style="width:150px" class="updateSelect"  data-placeholder="Choose Room..." required onchange="assignRoom(this.value)">');
  //           var roomSelect=$("select[name=room_name]"),
  //           rooms=JSON.parse(result);
  //           for(i=0;i<rooms.length;i++)
  //             roomSelect.append('<option value="' + rooms[i][0] +'">'+rooms[i][0]+ ' (' + rooms[i][1] +')</option>');
  //           var current = $(".selected").attr('id');
  //           if(current)
  //           {
  //             var current_room = $("input[name="+ current +"]","#courseAlloc").val().split(':')[1];
  //             if(current_room && current_room!="undefined")
  //               $("option[value='"+ current_room + "']",roomSelect).attr("selected", "selected");
  //             else
  //             {
  //               roomSelect.prop("selectedIndex", 0)
  //             }
  //             roomSelect.change();
  //             roomSelect.chosen();
  //           }
  //           else
  //             roomSelect.remove();
  //       }
  //     });
  //   })
  //   $("button").click(function(){$(".selected").click()}) // Refresh Room list on submit
  //   var active = $(".cell","#timetable").not(".disabled,.blank,.day,.time");
  //   active.droppable(
  //   {
  //       drop: function(e,ui)
  //       {
  //       <?php
  //         if(!$current["allowConflicts"]):
  //       ?>
  //         if($(this).hasClass('conflicting'))
  //         {
  //           $(this).removeAttr('style');
  //           $(this).addClass('showInfo');
  //           changes = true;
  //           $("input[name="+this.id+"]","#courseAlloc").remove();
  //           $("#conflict_help").html('<div class="center button"></div>');
  //           $.ajax({
  //             type: "POST",
  //             url: "allocate.php?action=queryConflict",
  //             data: "slot="+this.id+"&course="+ui.draggable[0].id,
  //             success: function(data)
  //             {
  //               $("#conflict_help").hide();
  //               $("#conflict_info").append(data);
  //             }
  //           })
  //           return;
  //         }
  //       <?php
  //         endif;
  //       ?>
  //         var i = ui.draggable.index()%colors.length;
  //         var inner = $('<div class="course_holder"></div>');
  //         $(this).html(inner);
  //         $.data(this,"content",inner);
  //         inner.html(ui.draggable.html());
  //         $(this).css('background-color',colors[i][0]);
  //         $(this).css('box-shadow','0 0 25px ' +colors[i][1]+ ' inset');
  //         $("input[name="+ this.id +"]","#courseAlloc").remove();
  //         changes = true;
  //         $("#courseAlloc").append('<input type="hidden" name="'+ this.id +'" value="'+ ui.draggable[0].id +":" + $("select[name=room_name]").val() +'">')
  //         $(this).click();
  //       },
  //       over: function(e,ui){
  //           var i= ui.draggable.index()%colors.length;
  //           if(!this.innerHTML)
  //           {
  //               $(this).css('background-color',colors[i][0]);
  //               $(this).css('box-shadow','0 0 25px ' +colors[i][1]+ ' inset');
  //           }
  //       },
  //       out: function(){
  //           if(!this.innerHTML)
  //               $(this).removeAttr('style');
  //       }
  //   });
  //   active.dblclick(function()
  //   {
  //       $(this).removeClass('selected');
  //       $(this).html('');
  //       $(this).removeAttr('style');
  //       $("input[name="+this.id+"]","#courseAlloc").remove();
  //   })
  //   $("input","#courseAlloc").each(function(){
  //       var slot = $("#"+this.name),
  //           inner = $('<div class="course_holder"></div>'),
  //           course = $("#"+this.value.split(':')[0].replace('/','\\/')),
  //           i=course.index()%colors.length;
  //       slot.html(inner);
  //       inner.html(course.html());
  //       slot.css('background-color',colors[i][0]);
  //       slot.css('box-shadow','0 0 25px ' +colors[i][1]+ ' inset');
  //   })
  //   colorCourses();
  //   $("option[value=<?=$_SESSION['faculty']?>]","#faculty").attr('selected','selected');
  //   $("#table_name").chosen();
  //   $("#faculty").chosen().change(function(){
  //     window.location.href='allocate.php?faculty='+this.value;
  //   })
  //   $("#table_name").change(function(){
  //     window.location.href='allocate.php?table='+this.value;
  //   })
  // })
  // function assignRoom(room)
  // {
  //   var slotId=$(".selected")[0].id;
  //   var slot=$("input[name=" + slotId + "]")[0];
  //   slot.value = slot.value.split(":")[0]+":"+room;
  // }
  // function resetInfo()
  // {
  //   $(".showInfo").html('');
  //   $("tr.data").remove();
  //   $("#conflict_help").html('&#9679; Drop a course into a conflicting slot to show conflict details');
  //   $("#conflict_help").show();
  //   $(".showInfo").removeClass('showInfo conflicting');
  // }
  // var changes = false;

  // window.onbeforeunload = function(e) {
  //   message = "There are unsaved changes in the timetable, are you sure you want to navigate away without saving them?.";
  //   if(changes)
  //   {
  //     e.returnValue = message;
  //     return message;
  //   }
  // }

 $( document ).ready(function() {
    // var dayTime = document.getElementBy
 		//Event Listener handler

    var setAllSlots = function() {
        $("input[type='radio'][value='anytime']").attr('checked', true);
        var optionNode = $("#courseId").children('option[value="' + $("#courseId").val() + '"]')[0];
        courseType = $(optionNode).attr('data');
        var slot1 = $("#course_slot1")[0];
        var slot2 = $("#course_slot2")[0];
        var slot3 = $("#course_slot3")[0];

        $(slot1).html('<option value="select_slot" label = " Select Slot"></option>');
        $(slot2).html('<option value="select_slot" label = " Select Slot"></option>');
        $(slot3).html('<option value="select_slot" label = " Select Slot"></option>');

        for(var i = 0; i < allSlots.length; i++) {
            // var singleSlot = allSlots[i];
            if( courseType == 'lab' && allSlots[i].lab == 1) { //checking if it is a lab course or not
                $(slot1).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');
                $(slot2).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');
                $(slot3).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');

            } else if( courseType!='lab' && allSlots[i].lab == 0) { //checking
                    $(slot1).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');
                    $(slot2).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');
                    $(slot3).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');
                }
            }
            $(slot1).trigger("chosen:updated");
            $(slot2).trigger("chosen:updated");
            $(slot3).trigger("chosen:updated");


        }

   	var setSlots = function(parentId){
        var optionNode = $("#courseId").children('option[value="' + $("#courseId").val() + '"]')[0];
        courseType = $(optionNode).attr('data');
        var slots;
        if(parentId == 'preference1')
            slots = $("#"+parentId).find("#course_slot1");
        else if(parentId == 'preference2')
            slots = $("#"+parentId).find("#course_slot2");
        else
            slots = $("#"+parentId).find("#course_slot3");
        // $(slots).trigger("chosen:updated");
        slots = slots[0];
        if(courseType=="nothing"){
            $(slots).html('');
            $(slots).append('<option label = "Select Slot"></option>');
            return true;
        }

        var sessions = $('#' + parentId).find("input[type='radio']");

        var session_value;
        for(var i = 0; i < sessions.length; i++){
            if(sessions[i].checked){
                session_value = sessions[i].value;
            }
        }
        $(slots).html('');
        $(slots).append('<option  label = "Select Slot"></option>');

        // $(slots).innerHTML = '<option value="select_slot">Select Slot</option>';
        for(var i = 0; i < allSlots.length; i++) {
            var singleSlot = allSlots[i];
            if( courseType == 'lab' && allSlots[i].lab == 1) { //checking if it is a lab course or not
                if(session_value == 'morning' && allSlots[i].tod == 'morning') {
                    $(slots).append( '<option value = "' + allSlots[i].id + '" label = "' + allSlots[i].id + '"></option>');
                } else if(session_value == 'evening' && allSlots[i].tod == 'evening') {
                    // $(slots).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');
                    $(slots).append( '<option value = "' + allSlots[i].id + '" label = "' + allSlots[i].id + '"></option>');

                } else if(session_value == 'anytime'){
                    // $(slots).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');
                    $(slots).append( '<option value = "' + allSlots[i].id + '" label = "' + allSlots[i].id + '"></option>');

                }
            } else if( allSlots[i].lab == 0 && courseType!='lab') { //checking
                if(session_value == 'morning' && allSlots[i].tod == 'morning') {
                    $(slots).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');
                    // $(slots).append( '<option value = "' + allSlots[i].id + '" label = "' + allSlots[i].id + '"></option>');

                } else if(session_value == 'evening' && allSlots[i].tod == 'evening') {
                    $(slots).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');
                    // $(slots).append( '<option value = "' + allSlots[i].id + '" label = "' + allSlots[i].id + '"></option>');

                } else if(session_value == 'anytime'){
                    $(slots).append( '<option value = "' + allSlots[i].id + '"> ' + allSlots[i].id + '</option>');
                    // $(slots).append( '<option value = "' + allSlots[i].id + '" label = "' + allSlots[i].id + '"></option>');

                }
            }
        }
        $(slots).trigger("chosen:updated");
   	}
    $("#courseId").change(function(){setAllSlots();});
    $('input[type=radio]').click(function(){
        var parentId = this.parentNode.id;
        setSlots(parentId);
    });
 });

  </script>
</head>

<body style="min-width: 1347px;">
  <div id="shadowhead"></div>
  <div id="header">
    <div id="account_info">
      <div class="infoTab"><div class="fixer"></div><div class="dashIcon usr"></div><div id="fName"><?=$_SESSION['fName']?></div></div>
      <div class="infoTab"><div class="fixer"></div><a href="logout.php" id="logout"><div class="dashIcon logout"></div><div>Logout</div></a></div>
    </div>
    <div id="header_text">QuickSlots v1.0</div>
  </div>
  <div id="nav_bar">
    <ul class="main_menu" id="main_menu">
    <?php
    if(sessionCheck('level','dean'))
      echo '<li class="limenu"><a href="dean.php">Manage Timetables</a></li>
            <li class="limenu"><a href="manage.php?action=departments">Manage Departments</a></li>
            <li class="limenu"><a href="manage.php?action=faculty">Manage Faculty</a></li>
            <li class="limenu"><a href="manage.php?action=batches">Manage Batches</a></li>
            <li class="limenu"><a href="manage.php?action=rooms">Manage Rooms</a></li>';
    ?>
            <li class="limenu"><a href="faculty.php">Manage Courses</a></li>
            <?php
              if(!sessionCheck('level', 'dean'))
               echo '<li class="limenu"><a href="addpreference.php">Add Preferences</a></li>';
             ?>
            <li class="limenu"><a href="allocate.php">Allocate Timetable</a></li>
            <li class="limenu"><a href="./">View Timetable</a></li>
    </ul>
  </div>
  <div id = "content" >
      <div class="center">
      <div class="box">
        <div class="boxbg"></div>
        <div class = "information"><div class="add icon"></div></div>
        <div class="title">Add Preference</div>
        <div class = "center">
       <form name = "preferencesForm" action = "preferences.php" method = "post" class="confirm">
          <select id = "courseId" class = "center stretch" name="course_id" style="width: 170px" data-placeholder = " Select Course" required>
          	<option label = " Select Course"></option>
              <?php
                $query = $db->prepare('SELECT course_id,type FROM courses WHERE fac_id = ?');
                $query -> execute([$_SESSION['faculty']]);
                while($course_id = $query->fetch()) {
                    echo '<option value="'.$course_id['course_id'].'" data="'.$course_id['type'].'">'.$course_id['course_id'].'</option>';
                }
              ?>
          </select> <br/>

          <?php
            echo '<script>  var allSlots = new Array();';
            foreach($db->query('SELECT id, lab, tod FROM slot_groups')as $all_slots)
            {
                    echo 'allSlots.push({id:"'.$all_slots['id'].'", lab:"'.$all_slots['lab'].'", tod:"'.$all_slots['tod'].'"});';
            }
            echo '</script>';
           ?>
          <div class = " preferencesClass" id = "preference1" style = "margin:5px ">
              <span class="inline" style="vertical-align: middle;padding-top:10px"><b></span></br>
              <input type = "radio" name = "session1" value = "anytime" checked>Any Time
              <input type="radio" name="session1" value="morning" > Morning
              <input type="radio" name="session1" value="evening"> Evening <br/>
              <span class="inline" style="vertical-align: middle;padding-top:10px"></span>

              <select class="stretch updateSelect" id = "course_slot1" name="course_slot1" style="width: 170px" data-placeholder = " Select Slot">
              	<option value="select_slot" label = " Select Slot"></option>

              </select> <br/>
          </div>
          <div class = " preferencesClass" id = "preference2" style = "margin:5px 5px">
              <input type = "radio" name = "session2" value = "anytime" checked>Any Time
              <input type="radio" name="session2" value="morning" > Morning
              <input type="radio" name="session2" value="evening"> Evening <br/>
              <select class="stretch updateSelect" id = "course_slot2" name="course_slot2" style="width: 170px" data-placeholder = " Select Slot">
                <option value="select_slot" label = " Select Slot"></option>

              </select> <br/>
        </div>
          <div class = " preferencesClass" id = "preference3" style = "margin:5px 5px ">
              <input type = "radio" name = "session3" value = "anytime" checked>Any Time
              <input type="radio" name="session3" value="morning" > Morning
              <input type="radio" name="session3" value="evening"> Evening <br/>
              <select class="stretch updateSelect" id = "course_slot3" name="course_slot3" style="width: 170px" data-placeholder = " Select Slot">
                <option value="select_slot"  label = "Select Slot"></option>
              </select> <br/>
        </div>
        <input type="hidden" id="confirm_msg" value="Are you sure you want to submit the preferences?">
        <p class="info"></p>
        <div class = "center button">
        <button>Submit</button></div>
        </form>
    </div>
    </div>

  <div class="box">
    <div class="boxbg"></div>
    <div class = "information"><div class="icon remove"></div></div>
    <div class="title">Delete Preference</div>
    <div class="elements">
      <form method="post" action="preferences.php?action=del" class="confirm" onsubmit="setTimeout(function () { window.location.reload(); }, 10)">
        <select name="preference_id" class="updateSelect stretch" data-placeholder="Choose Preference..." required>
          <option label="Choose Preference..."></option>
          <?php
                //$query = $db->prepare('SELECT course_id,type FROM courses WHERE fac_id = ?');
                $query = $db->prepare('SELECT * FROM preferences WHERE course_id IN (SELECT course_id FROM courses WHERE fac_id = ?)');
                $query -> execute([$_SESSION['faculty']]);
                while($pref = $query->fetch()) {
                    echo '<option value="'.$pref['id'] .'">'.$pref['course_id'] . " (" . $pref['slot_group'] . ")".'</option>';
                }
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
    </div>
</div>



</body>
</html>
