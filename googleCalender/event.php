<?php
require_once __DIR__ . '/vendor/autoload.php';

session_start();
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secrets.json');
define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR)
));

function getClient() {
  $client = new Google_Client();
  $client->setScopes(SCOPES);
  $client->setAuthConfigFile(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
  }
  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->refreshToken($client->getRefreshToken());
  }
  return $client;
}

function updateDB($id_list) {
  $query = "SELECT COUNT(*) FROM events where fac_id = 'subruk'";
  $retval = mysql_query($query);
  $count = mysql_fetch_array( $retval );
  if($count['COUNT(*)']==='0')
    $query = "INSERT INTO events VALUES('subruk', '$id_list')";
  else 
    $query = "update events set eventID='$id_list' where fac_id='1'";
  mysql_query($query);
  printf("Updated db \n");
}

function add_events($event_array, $service) {
  foreach ($event_array as &$values){
    $event = new Google_Service_Calendar_Event($values);
    $event = $service->events->insert('primary', $event);
    $id_list .= ',' . $event->getId();
  }
  $id_list = mysql_real_escape_string($id_list);
  //updating the events table with the latest event IDs added to the google calender
  updateDB($id_list);
  printf("added events \n");
}

function delete_events($fac_id, $service) {
  $fac_id = mysql_real_escape_string($fac_id);
  $query = "select * from events where fac_id='subruk'";
  $retval = mysql_query($query);
  $id_list = mysql_fetch_array( $retval );
  $id_arr = explode(',', $id_list['event_id']);
  print_r($id_arr);
  foreach ($id_arr as &$values)
      if ($values!='')
        $service->events->delete('primary', $values);
}

$client = getClient();
$service = new Google_Service_Calendar($client);


$username="root";
$password="123";
$database="quickslots";

mysql_connect(localhost,$username,$password);
@mysql_select_db($database) or die( "Unable to select database");

//delete the previously synced caleder
$fac_id = 'subruk';
$fac_id = mysql_real_escape_string($fac_id);

delete_events($fac_id, $service);

$query="select A.course_id, course_name, day, slot_num, room, table_name  from courses as A join slot_allocs as B where A.course_id=B.course_id and A.fac_id='$fac_id'";
$retval = mysql_query($query);


// semester starting date
$semStartDate = 1;
$semEndDate = 30;
$semStartMonth = intval(date('m'));
if($semStartMonth<=4){
  $semStartMonth = 1;
  $semEndMonth = 4;
}
else{
  $semStartMonth = 8;
  $semEndMonth = 11;  
}
$year = date('Y');


// monday ==> 1, sunday==>0
$semStartDay = date('w', strtotime("".$year."-".$semStartMonth."-".$semStartDate));

$slot_mapping = array(
        '09:00:00-09:55:00',
        '10:00:00-10:55:00',
        '11:00:00-11:55:00',
        '12:00:00-12:55:00',
        '13:00:00-14:25:00',
        '14:30:00-15:55:00',
        '16:00:00-17:25:00',
        '17:30:00-19:00:00',
        '19:00:00-20:30:00',
);

$event_array = array();

while($row = mysql_fetch_array($retval, MYSQL_ASSOC))
{
  $dayOffset = $row['day']-$semStartDay;
  if ($dayOffset<0)
    $dayOffset += 7;

  $firstclassDate = $semStartDate + ($dayOffset)%7;

  $eventDate = date_format(date_create($year."-".$semStartMonth."-".$firstclassDate, timezone_open('Asia/Kolkata')), 'Y-m-d');

  // get event's start and end time
  $timestring = explode('-', $slot_mapping[$row['slot_num']-1]);
  $eventStarttime = $timestring[0];
  $eventEndtime = $timestring[1];

  $untilDate = date_format(date_create($year."-".$semEndMonth."-".$semEndDate, timezone_open('Asia/Kolkata')), 'Ymd');

  $event=array(
  'summary' => $row['course_id'] . " : " . $row['course_name'],
  'location' => $row['room'],

  'start' => array(
    'dateTime' => $eventDate."T".$eventStarttime."+05:30",
    'timeZone' => 'Asia/Kolkata',
  ),
  'end' => array(
    'dateTime' => $eventDate."T".$eventEndtime."+05:30",
    'timeZone' => 'Asia/Kolkata',
  ),
    'recurrence' => array(
    'RRULE:FREQ=WEEKLY;UNTIL='.$untilDate.'T235959Z'
  ),

  'reminders' => array(
    'useDefault' => FALSE,
    'overrides' => array(
      array('method' => 'email', 'minutes' => 24 * 60),
      array('method' => 'popup', 'minutes' => 10),
    ),
  ),

 );
  // push the above event now to array
  array_push($event_array,$event);
}

//add events to calendar
$event_array=array();
add_events($event_array, $service);
mysql_close();