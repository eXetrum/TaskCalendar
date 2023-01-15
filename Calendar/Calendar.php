
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8' />
<link href='css/style.css' rel='stylesheet' />
<link href='css/fullcalendar.min.css' rel='stylesheet' />
<link href='css/fullcalendar.print.min.css' rel='stylesheet' media='print' />
<script src='js/moment.min.js'></script>
<script src='js/jquery.min.js'></script>
<script src='js/fullcalendar.min.js'></script>

<script>
function setCookie(cookieName, cookieValue, numOfDays) {
    var today = new Date();
    var expire = new Date();

    if (numOfDays <= 0) numOfDays = 1;

    expire.setTime(today.getTime() + 3600000*24*numOfDays);
    document.cookie = cookieName+"="+escape(cookieValue) + ";expires="+expire.toGMTString();
}

function getCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}
function changeUser(user_id) {
	// Save selected user_id into cookies
	setCookie('user_id', user_id, 1); // 1 day from now
}
</script>

<style>

	body {
		margin: 40px 10px;
		padding: 0;
		font-family: "Lucida Grande",Helvetica,Arial,Verdana,sans-serif;
		font-size: 14px;
	}

	#calendar {
		max-width: 900px;
		margin: 0 auto;
	}

</style>
</head>
<body>
<?php 
$backBtn = "<a href='" . basename(__FILE__) . "' > Back </a>";

function dbConnect() {
	$host = "localhost";
	$user = "root";
	$pass = "toor";
	$dbname = "calendar";
	// Connect to database 
	$conn = mysql_connect($host, $user, $pass);
    
    if (!$conn) {
        echo "Unable to connect to DB: " . mysql_error();
        exit;
    }
    
    if (!mysql_select_db($dbname)) {
        echo "Unable to select $dbname: " . mysql_error();
        exit;
    }
	return $conn;
}

// Read all events(tasks) from MySql database and construct json array of objects 
function readAllEvents() {
	$table = "task";
	// Connect to database 
	$conn = dbConnect();
	// Construct query    
    $sql = "SELECT * FROM $table";
	// Execute query
    $result = mysql_query($sql);

    if (!$result) {
        echo "Could not successfully run query ($sql) from DB: " . mysql_error();
        exit;
    }
    
    if (mysql_num_rows($result) == 0) {
        echo "No rows found, nothing to print so am exiting";
        exit;
    }
	
	$event_data = array();

    while ($row = mysql_fetch_assoc($result)) {
		$event_data[] = array(
			"title" 	=> 	$row["task_title"],
			"start"		=>	$row["task_date"],
			"url"		=>	basename(__FILE__) . "?task_id=" . $row["task_id"],
			"object"	=>	$row
		);
    }
	
    mysql_free_result($result);
	
	return $event_data;
}

function readAllUsers() {
	$table = "user";
	// Connect to database 
	$conn = dbConnect();
	// Construct query    
    $sql = "SELECT * FROM $table";
	// Execute query
    $result = mysql_query($sql);

    if (!$result) {
        echo "Could not successfully run query ($sql) from DB: " . mysql_error();
        exit;
    }
    
    if (mysql_num_rows($result) == 0) {
        echo "No rows found, nothing to print so am exiting";
        exit;
    }
	
	$users = array();

    while ($row = mysql_fetch_assoc($result)) {
		$users[] = $row;
    }
	
    mysql_free_result($result);
	return $users;
}

function readUserDetails($user_id) {
	$userDetails = array();
	
	$table = "user";
	// Connect to database 
	$conn = dbConnect();
	// Construct query    
    $sql = "SELECT * FROM $table WHERE user_id=$user_id";
	// Execute query
    $result = mysql_query($sql);

    if (!$result) {
        echo "Could not successfully run query ($sql) from DB: " . mysql_error();
        exit;
    }
    
    if (mysql_num_rows($result) == 0) {
        echo "No rows found, nothing to print so am exiting";
        exit;
    }
	

    while ($row = mysql_fetch_assoc($result)) {
		$userDetails[] = $row;
    }
	
    mysql_free_result($result);
	
	return $userDetails[0];
}

function readTaskDetails($task_id, $user_id) {
	global $backBtn;
	$taskDetails = array();
	
	if(empty($user_id) || !is_numeric($user_id)) {
		echo "Incorrect value for User ID.<br/>" . $backBtn;
		return null;
	}
	
	if(empty($task_id) || !is_numeric($task_id)) {
		echo "Incorrect value for Task ID.<br/>" . $backBtn;
		return null;
	}
	
	
	$user = readUserDetails($user_id);
	if(count($user) == 0) {
		echo "User with ID=$user_id not found.<br/>" . $backBtn;
		return null;
	}
	
	
	$table = "task";
	// Connect to database 
	$conn = dbConnect();
	// Construct query    
    $sql = "SELECT * FROM $table WHERE task_id='$task_id' AND user_id='$user_id'";
	// Execute query
    $result = mysql_query($sql);

    if (!$result) {
        echo "Could not successfully run query ($sql) from DB: " . mysql_error();
        exit;
    }
    
    if (mysql_num_rows($result) == 0) {
		echo "User " . $user["user_name"] . " with user_ID='$user_id' is not allowed to view task with task_ID ='$task_id' <br/>" . $backBtn;
        exit;
    }
	

    while ($row = mysql_fetch_assoc($result)) {
		$taskDetails[] = $row;
    }
	
    mysql_free_result($result);
	
	
	return $taskDetails[0];
}



if(isset($_GET["task_id"])) {
	$task = readTaskDetails($_GET["task_id"], $_COOKIE["user_id"]);
	$user = readUserDetails($_COOKIE["user_id"]);
	if($task == null) {
		echo $backBtn;
		exit;
	}

	echo "<h2>Task ID=" . $task["task_id"] . "</h2>";
	echo "<h2>User ID=" . $user["user_id"] . "</h2><hr/>";
	echo "<h3>Date: <i>" . $task["task_date"] . "</i></h3>";
	echo "<h3>Title: <i>" . $task["task_title"] . "</i></h3>";
	echo "<h3>File: " . $task["task_file"] . "</h3><a href='#' onclick='return false;' >" . $task["task_filename"] . "</a><br/>";
	echo "<h3>Description: </h3><div class='task_details' >" . $task["task_description"] . "</div><br/>";
	echo "<h3>State: " . $task["task_state"] . "</h3><hr/>";
	
	echo $backBtn;
	
} else {
	
?>

<select onchange='changeUser(this.options[this.selectedIndex].value);' >
<?php 
$users = readAllUsers();
$selected = $_COOKIE["user_id"];
foreach($users as $user) { echo '<option ' . ($selected == $user["user_id"] ? "selected" : "") . ' value='.$user["user_id"].'>'.$user["user_name"]. '(' . $user["user_id"] . ')</option>'; } 
?>
</select>

<div id='calendar'></div>
<script>

	$(document).ready(function() {

		$('#calendar').fullCalendar({
			defaultDate: new Date(),//'2017-04-12',
			editable: false,
			eventLimit: true, // allow "more" link when too many events
			events: <?php echo  json_encode(readAllEvents()); ?>,
			eventClick: function(calEvent, jsEvent, view) {
				console.log(calEvent);
			}
		});
		
	});

</script>
<?php 
}
?>
</body>
</html>
