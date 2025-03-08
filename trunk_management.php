<?php
session_start(); // Ensure session is started

// Generate unique file paths based on session ID
$session_id = session_id();
$ip_file = __DIR__ . '/ip_addresses/' . $session_id . '_ip_address.txt';
$db_file = __DIR__ . '/ip_addresses/' . $session_id . '_db_output.txt';

// Check if the IP address file exists
if (!file_exists($ip_file)) {
    die('IP address file not found. Please enter the IP address first.');
}

$ip_address = trim(file_get_contents($ip_file));

if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
    die('Invalid IP address in file');
}

// Check if the database configuration file exists
if (!file_exists($db_file)) {
    die('Database configuration file not found.');
}


// Read and parse the database configuration file
$db_config = [];
$db_lines = file($db_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($db_lines as $line) {
    if (preg_match('/^\$(\w+)\["(\w+)"\] = "(.*)";$/', $line, $matches)) {
        $db_config[$matches[2]] = $matches[3];
    }
}

// Extract database credentials
$db_user = $db_config['AMPDBUSER'] ?? '';
$db_pass = $db_config['AMPDBPASS'] ?? '';
$db_host = $db_config['AMPDBHOST'] ?? '';
$db_name = $db_config['AMPDBNAME'] ?? '';

// URL of the FreePBX server's execute.php script
$execute_url = 'http://' . $ip_address . '/execute.php';

// Function to send a command to execute.php
function sendCommand($command) {
    global $execute_url;
    
    $postData = json_encode(['command' => $command]);

    $ch = curl_init($execute_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        die("Curl error: " . curl_error($ch));
    }
    
    curl_close($ch);

    if ($http_code != 200) {
        die("HTTP error: " . $http_code . " Response: " . htmlspecialchars($response));
    }
    
    return json_decode($response, true);
}

// Command to query the trunk balance data from the asteriskcdrdb database
$db_query_command = "mysql -u $db_user -p'$db_pass' -h $db_host -e 'USE asteriskcdrdb; SELECT * FROM trunk_balance;'";

// Execute the command
$response = sendCommand($db_query_command);

if (isset($response['error'])) {
    die("Error executing command: " . htmlspecialchars($response['error']));
}

// Initialize the array to store trunk balance data
$trunkBalanceData = [];

if (isset($response['output'])) {
    $lines = explode("\n", $response['output']);
    
    // Skip the first line if it's a header row
    $headers = explode("\t", array_shift($lines));
    
    foreach ($lines as $line) {
        $fields = explode("\t", $line);
        if (count($fields) >= 6) {
            $trunkBalanceData[] = [
                'trunk_name' => $fields[0],
                'total_trunk_minutes' => $fields[1],
                'total_calls' => $fields[2],
                'remaining_trunk_minutes' => (int)$fields[3],
                'remaining_calls' => $fields[4],
                'plan_expiry_date' => $fields[5]
            ];
        }
    }
}

function convertSecondsToTimeFormat($minutes) {
    return sprintf('%d:00', $minutes);
}

// Function to list trunks from the asterisk database
function trunkbalance_listtrunk() {
    global $execute_url, $db_user, $db_pass, $db_host;

    $db_query_command = "mysql -u $db_user -p'$db_pass' -h $db_host -e 'USE asterisk; SELECT name FROM trunks;'";
    $response = sendCommand($db_query_command);
    
    $trunks = [];

    if (isset($response['output'])) {
        $lines = explode("\n", $response['output']);
        array_shift($lines); // Skip the headers
        foreach ($lines as $line) {
            if (!empty($line)) {
                $trunks[] = ['name' => $line];
            }
        }
    }
    return $trunks;
}
?>


<meta http-equiv="refresh" content="60">








<?php include 'loader.html'; ?>

 
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <title>TechVision365</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://translate.google.com/translate_static/css/translateelement.css">
 
    
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
           
@import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800,900');
    body{
      font-family: 'Poppins', sans-serif;
      font-weight: 400;
      font-size: 15px;
      line-height: 1.7;
      color: #1f2029;
        background-color: #fff;
        overflow-y: auto;

      background-image: url('https://assets.codepen.io/1462889/back-page.svg');
      background-position: center;
      background-repeat: no-repeat;
      background-size: 101%;
    }
    p{
      font-family: 'Poppins', sans-serif;
      font-weight: 400;
      font-size: 16px;
      line-height: 1.7;
      color: #1f2029;
    }
    
    .section{
      position: relative;
      width: 100%;
      display: block;
      display: -ms-flexbox;
      display: flex;
      -ms-flex-wrap: wrap;
      flex-wrap: wrap;
      -ms-flex-pack: center;
      justify-content: center;
    }
    .full-height{
      min-height: 100vh;
    }
    
    [type="checkbox"]:checked,
    [type="checkbox"]:not(:checked){
      position: absolute;
      left: -9999px;
    }
    .modal-btn:checked + label,
    .modal-btn:not(:checked) + label{
      position: relative;
      font-family: 'Poppins', sans-serif;
      font-weight: 500;
      font-size: 15px;
      line-height: 2;
      height: 50px;
      transition: all 200ms linear;
      border-radius: 4px;
      width: 240px;
      letter-spacing: 1px;
      display: -webkit-inline-flex;
      display: -ms-inline-flexbox;
      display: inline-flex;
      text-align: center;
      border: none;
      cursor: pointer;
      background-color: #102770;
      color: #ffeba7;
      box-shadow: 0 12px 35px 0 rgba(16,39,112,.25);
    }
    .modal-btn:not(:checked) + label:hover{
      background-color: #ffeba7;
      color: #102770;
    }
    .modal-btn:checked + label .uil,
    .modal-btn:not(:checked) + label .uil{
      margin-left: 10px;
      font-size: 18px;
    }
    .modal-btn:checked + label:after,
    .modal-btn:not(:checked) + label:after{
      position: fixed;
      top: 30px;
      right: 30px;
      z-index: 110;
      width: 40px;
      border-radius: 3px;
      height: 30px;
      text-align: center;
      line-height: 30px;
      font-size: 18px;
      background-color: #ffeba7;
      color: #102770;
      font-family: 'unicons';
      content: '\eac6'; 
      box-shadow: 0 12px 25px 0 rgba(16,39,112,.25);
      transition: all 200ms linear;
      opacity: 0;
      pointer-events: none;
      transform: translateY(20px);
    }
    .modal-btn:checked + label:hover:after,
    .modal-btn:not(:checked) + label:hover:after{
      background-color: #102770;
      color: #ffeba7;
    }
    .modal-btn:checked + label:after{
      transition: opacity 300ms 300ms ease, transform 300ms 300ms ease, background-color 250ms linear, color 250ms linear;
      opacity: 1;
      pointer-events: auto;
      transform: translateY(0);
    }
    .modal{
      position: fixed;
      display: block;
      display: -ms-flexbox;
      display: flex;
      -ms-flex-wrap: wrap;
      flex-wrap: wrap;
      -ms-flex-pack: center;
      justify-content: center;
      margin: 0 auto;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 100;
      overflow-x: hidden;
      background-color: rgba(31,32,41,.75);
      pointer-events: none;
      opacity: 0;
      transition: opacity 250ms 700ms ease;
    }
    .modal-btn:checked ~ .modal{
      pointer-events: auto;
      opacity: 1;
      transition: all 300ms ease-in-out;
    }
    .modal-wrap {
      position: relative;
      display: block;
      width: 100%;
      background-image: url('https://assets.codepen.io/1462889/back-page.svg');
      max-width: 450px;
      /* height:250px; */
      margin: 0 auto;
      margin-top: 20px;
      margin-bottom: 20px;
      border-radius: 4px;
      overflow: hidden;
      padding-bottom: 20px;
      background-color: #fff;
        -ms-flex-item-align: center;
        align-self: center;
        box-shadow: 0 12px 25px 0 rgba(199,175,189,.25);
      opacity: 0;
      transform: scale(0.6);
      transition: opacity 250ms 250ms ease, transform 300ms 250ms ease;
    }
    .modal-wrap img {
      display: block;
      width: 100%;
      height: auto;
    }
    .modal-wrap p {
      padding: 20px 30px 0 30px;
    }
    .modal-btn:checked ~ .modal .modal-wrap{
      opacity: 1;
      transform: scale(1);
      transition: opacity 250ms 500ms ease, transform 350ms 500ms ease;
    }
    
    
    .logo {
      position: absolute;
      top: 25px;
      left: 25px;
      display: block;
      z-index: 1000;
      transition: all 250ms linear;
    }
    .logo img {
      height: 26px;
      width: auto;
      display: block;
        filter: brightness(10%);
      transition: filter 250ms 700ms linear;
    }
    .modal-btn:checked ~ .logo img {
        filter: brightness(100%);
      transition: all 250ms linear;
    }
    
    
    @media screen and (max-width: 500px) {
      .modal-wrap {
        width: calc(100% - 40px);
        padding-bottom: 15px;
      }
      .modal-wrap p {
        padding: 15px 20px 0 20px;
      }
    }
    .content-table {
    width: 80%;
    margin: 25px auto;
    border-collapse: collapse;
    font-size: 0.9em;
    border-radius: 5px 5px 0 0;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
    table-layout: auto; /* Allows table to adjust columns automatically */
}

.content-table thead tr {
    background-color: #009879;
    color: #ffffff;
    text-align: left;
    font-weight: bold;
}

.content-table th,
.content-table td {
    padding: 12px 15px;
    border: 1px solid #dddddd; /* Add border to cells */
    word-wrap: break-word; /* Ensures long content wraps to the next line */
}

.content-table tbody tr {
    border-bottom: 1px solid #dddddd;
}

.content-table tbody tr:nth-of-type(even) {
    background-color: #f3f3f3;
}

.content-table tbody tr:hover {
    background-color: #f1f1f1; /* Add hover effect */
}

.content-table tbody tr:last-of-type {
    border-bottom: 2px solid #009879;
}

.content-table tbody tr.active-row {
    font-weight: bold;
    color: #009879;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .content-table {
        width: 100%; /* Use full width on small screens */
        font-size: 0.8em; /* Slightly smaller font size */
    }

    .content-table th,
    .content-table td {
        padding: 10px 8px; /* Reduce padding for smaller screens */
    }
}

@media (max-width: 480px) {
    .content-table {
        font-size: 0.8em; /* Adjust font size for small screens */
        overflow-x: auto; /* Add horizontal scroll for small screens */
    }

    .content-table th,
    .content-table td {
        padding: 10px 5px; /* Reduce padding for small screens */
    }

    /* Make the table scrollable */
    .content-table {
        display: block;
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
    }

    .content-table thead {
        display: table-header-group; /* Ensure the table header is always visible */
    }

    .content-table tbody tr {
        display: table-row; /* Ensure table rows are displayed correctly */
    }

    .content-table th,
    .content-table td {
        display: table-cell; /* Ensure cells are displayed correctly */
        white-space: normal; /* Allow text wrapping */
    }
}

.modal-label {
        display: flex;
        align-items: center; /* Align vertically */
        justify-content: center; /* Align horizontally */
        text-align: center; /* Align text within the label */
    }
    .button1{
        background-color: red;
  border-radius: 8px;
  border-style: none;
  box-sizing: border-box;
  color: #FFFFFF;
  cursor: pointer;
  display: inline-block;
  font-family: "Haas Grot Text R Web", "Helvetica Neue", Helvetica, Arial, sans-serif;
  font-size: 14px;
  font-weight: 500;
  height: 40px;
  line-height: 20px;
  list-style: none;
  margin: 0;
  outline: none;
  padding: 10px 16px;
  position: relative;
  text-align: center;
  text-decoration: none;
  transition: color 100ms;
  vertical-align: baseline;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
    }
    .button2{
        background-color: blue;
  border-radius: 8px;
  border-style: none;
  box-sizing: border-box;
  color: #FFFFFF;
  cursor: pointer;
  display: inline-block;
  font-family: "Haas Grot Text R Web", "Helvetica Neue", Helvetica, Arial, sans-serif;
  font-size: 14px;
  font-weight: 500;
  height: 40px;
  line-height: 20px;
  list-style: none;
  margin: 0;
  outline: none;
  padding: 10px 16px;
  position: relative;
  text-align: center;
  text-decoration: none;
  transition: color 100ms;
  vertical-align: baseline;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
    }
    </style>
      <script>
    
    function deleteTrunk(trunkName) {
    if (confirm('Are you sure you want to delete this trunk entry?')) {
        // Submit the form to delete the trunk entry
        var form = new FormData();
        form.append('deleteTrunk', trunkName);

        fetch('trunk_process.php', {
            method: 'POST',
            body: form
        }).then(response => {
            if (response.ok) {
                // Reload the page after successful deletion
                location.reload();
            } else {
                alert('Error: ' + response.statusText);
            }
        }).catch(error => {
            console.error('Error:', error);
        });
    }
}

        function resetTrunk(trunkName) {
            if (confirm('Are you sure you want to reset this trunk entry?')) {
                // Submit the form to reset the trunk entry
                var form = new FormData();
                form.append('resetTrunk', trunkName);

                fetch('trunk_process.php', {
                    method: 'POST',
                    body: form
                }).then(response => {
                    if (response.ok) {
                        // Reload the page after successful reset
                        location.reload();
                    } else {
                        alert('Error: ' + response.statusText);
                    }
                }).catch(error => {
                    console.error('Error:', error);
                });
            }
        }

   

</script>

</head>
<body>
<div class="sidebar close">
<div class="logo-details">
    <img src="logo.png" alt="Logo" style="width: 50px; height: 50px; margin-left:5%;">
    <span class="logo_name">TechVision365</span>
</div>

<ul class="nav-links">
      <li>
        <a href="index.php">
          <i class='bx bx-grid-alt' ></i>
          <span class="link_name">Dashboard</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="index.php">Dashboard</a></li>
        </ul>
      </li>
      <li>
        <div class="iocn-link">
          <a href="#">
          <i class='bx bx-bluetooth'></i>


            <span class="link_name">Bluetooth Setup</span>
          </a>
          <i class='bx bxs-chevron-down arrow' ></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="#">Bluetooth Setup</li>
          <li><a href="bluetooth5.php">Configure Bluetooth
          </a></li>
          <li><a href="system_alias.php">Setup System Alias</a></li>
        
          
         
        </ul>
      </li>
      <li>
        <a href="check_device.php">
        <i class='bx bx-mobile' ></i>
          <span class="link_name">Connected Mobiles</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="check_device.php">Connected Mobiles</a></li>
        </ul>
      </li>
      <li>
   
        <a href="trunk_management.php">
        <i class='bx bx-server' ></i>
          <span class="link_name">Trunk Utilization Settings</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="trunk_management.php">Trunk Utilization Settings</a></li>
        </ul>
      </li>
      <li>
        <a href="trunk_reports.php">
        <i class='bx bx-data' ></i>
          <span class="link_name">Trunk Reports</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="trunk_reports.php">Trunk Usage Reports 

          </a></li>
        </ul>
      </li>
      <li>
        <a href="demo_call.php">
        <i class='bx bx-phone'></i>
          <span class="link_name">Demo Call</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="demo_call.php">Demo Call
          </a></li>
        </ul>
      </li>
     
     

</ul>
  </div>
  <section class="home-section" >
    
    <div class="home-content">

      <i class='bx bx-menu' ></i>
      <span class="text">TechVision365</span>
    </div>
    <!-- <div id="google_translate_element">

    </div> -->
    

		<!-- MAIN -->
		<main>
		
		
			
 
      <h1 style="text-align: center;">Trunk Management</h1>
    <table class="content-table">
        <thead>
            <tr>
                <th>Trunk Name</th>
                <th>Total Trunk Minutes</th>
                <th>Total Calls</th>
                <th>Remaining Trunk Minutes</th>
                <th>Remaining Calls</th>
                <th>Expiry Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($trunkBalanceData)) {
                foreach ($trunkBalanceData as $row) {
                    ?>
                    <tr id='trunkRow_<?= htmlspecialchars($row['trunk_name']) ?>'>
                        <td><?= htmlspecialchars($row['trunk_name']) ?></td>
                        <td><?= htmlspecialchars($row['total_trunk_minutes']) ?></td>
                        <td><?= htmlspecialchars($row['total_calls']) ?></td>
                        <td><?= htmlspecialchars(convertSecondsToTimeFormat($row['remaining_trunk_minutes'])) ?></td>
                        <td><?= htmlspecialchars($row['remaining_calls']) ?></td>
                        <td><?= htmlspecialchars($row['plan_expiry_date']) ?></td>
                        <td>
                            <button onclick="deleteTrunk('<?= htmlspecialchars($row['trunk_name']) ?>')" class="button1">Delete</button>
                            <button onclick="resetTrunk('<?= htmlspecialchars($row['trunk_name']) ?>')" class="button2">Reset</button>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='7'>No data available</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <div class="section full-height">
    <br><br><br>
    <input class="modal-btn" type="checkbox" id="modal-btn" name="modal-btn"/>
    <label for="modal-btn" class="modal-label">Update & Set Trunks Data</label>
    <div class="modal">
      <div class="modal-wrap">
        <label for="modal-btn" class="close-modal" style="position: absolute; top: 10px; right: 10px; cursor: pointer;">
        <i class='bx bx-x' style="font-size: 24px; color: #000;"></i>

        </label>
        <form id="trunkBalanceForm" method="post" action="trunk_process.php">
        <h1 style="text-align: center;">Trunk Data</h1>
<select name="selected_trunk" id="selectedTrunk" placeholder='Select a Trunk' style='width: 70%; margin-top: 5%; margin-left:5%; padding: 12px; border: 1px solid #ccc; border-radius: 7px;' required>
    <option value="all">All</option>
    <?php
    $trunkList = trunkbalance_listtrunk();
    foreach ($trunkList as $trunk) {
        echo "<option value='{$trunk['name']}'>{$trunk['name']}</option>";
    }
    ?>
</select>

          <br>
          <input type="text" name="totalTrunkMinutes" id="totalTrunkMinutes" placeholder='Enter the Total Minutes' style='width: 70%; margin-top: 5%; margin-left:5%; padding: 12px; border: 1px solid #ccc; border-radius: 7px;' required>
          <br>
          <input type="text" name="totalCalls" id="totalCalls" placeholder='Enter the Total Calls' style='width: 70%; margin-top: 5%; margin-left:5%; padding: 12px; border: 1px solid #ccc; border-radius: 7px;' required>
          <input type="date" name="planexpirydate" id="planExpiryDate" placeholder="Select Plan Expiry Date" style='width: 70%; margin-top: 5%; margin-left:5%; padding: 12px; border: 1px solid #ccc; border-radius: 7px;' required>
          <br><br>
          <button type="submit" style='background-color: black; border-radius: 8px; border-style: none; margin-left:5%; box-sizing: border-box; color: #FFFFFF; cursor: pointer; display: inline-block; font-family: "Haas Grot Text R Web", "Helvetica Neue", Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 500; height: 40px; line-height: 20px; list-style: none; margin: 0; outline: none; padding: 10px 16px; position: relative; text-align: center; text-decoration: none; transition: color 100ms; vertical-align: baseline; user-select: none; -webkit-user-select: none; touch-action: manipulation;'>Submit</button>
        </form>
      </div>
    </div>
</div>

			
		</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->
	

			
	</main>
            

    



  
			</section>
			<?php include 'footer.html'; ?>
			<script src="index.js"></script>
		
		  
			<script>
			let arrow = document.querySelectorAll(".arrow");
			for (var i = 0; i < arrow.length; i++) {
			  arrow[i].addEventListener("click", (e)=>{
			 let arrowParent = e.target.parentElement.parentElement;//selecting main parent of arrow
			 arrowParent.classList.toggle("showMenu");
			  });
			}
			let sidebar = document.querySelector(".sidebar");
			let sidebarBtn = document.querySelector(".bx-menu");
			console.log(sidebarBtn);
			sidebarBtn.addEventListener("click", ()=>{
			  sidebar.classList.toggle("close");
			});
			function googleTranslateElementInit() {
			new google.translate.TranslateElement({pageLanguage: 'en'}, 'google_translate_element');
		  }
		  // Add this script to your existing JavaScript file or in a script tag in the body section
		  
		  document.addEventListener('DOMContentLoaded', function () {
			  const profilePic = document.querySelector('.profile-pic');
			  const dropdownMenu = document.querySelector('.dropdown-menu');
		  
			  // Toggle the dropdown on profile picture click
			  profilePic.addEventListener('click', function () {
				  dropdownMenu.classList.toggle('show-dropdown');
			  });
		  
			  // Close the dropdown if user clicks outside
			  document.addEventListener('click', function (event) {
				  if (!profilePic.contains(event.target)) {
					  dropdownMenu.classList.remove('show-dropdown');
				  }
			  });
		  });
		  
			</script>
			 <script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
		   
		
		  
   
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha256-FNlMDE/Q1G0EN2Os0Y2F8DByVvPKn+KZV6AyI0puZpo= sha512-J+G8FLHCFmuBY9ls5nEBt4N/Ufa80rXNOvDCVaXO/0R/4RXweCfA+jYTdL8/Wczfg/5f5fbGoEMRR+PxRvz8ug==" crossorigin="anonymous" />
<script>  function sprintf(format) {
            for (var i = 1; i < arguments.length; i++) {
                format = format.replace('%' + i, arguments[i]);
            }
            return format;
        }
        
        
        function openPopupForm() {
        document.getElementById('popupForm').style.display = 'flex';
    }</script>
</body>
</html>

   

