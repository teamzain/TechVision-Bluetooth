<?php

session_start();

// Generate the unique file for the current user
$session_id = session_id();
$file = __DIR__ . DIRECTORY_SEPARATOR . 'ip_addresses' . DIRECTORY_SEPARATOR . $session_id . '_ip_address.txt';

if (!file_exists($file)) {
    die('IP address file not found. Please enter the IP address first.');
}

$ip_address = trim(file_get_contents($file));

if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
    die('Invalid IP address in file');
}

// URL of the FreePBX server's execute.php script
$url = 'http://' . $ip_address . '/execute.php';

// Handle the action for listing controllers and setting an alias
$action = isset($_POST['action']) ? htmlspecialchars($_POST['action']) : '';

$controllers = [];
$devices = [];
$selectedPairController = isset($_POST['controller_mac_pair']) ? htmlspecialchars($_POST['controller_mac_pair']) : '';
$selectedRemoveController = isset($_POST['controller_mac_remove']) ? htmlspecialchars($_POST['controller_mac_remove']) : '';
$newAlias = '';

if ($action === 'list_controllers') {
    $controllers = listControllers($url);
} elseif ($action === 'select_controller_pair' && isset($_POST['select_controller_index_pair'])) {
    $index = intval($_POST['select_controller_index_pair']);
    $selectedPairController = htmlspecialchars($_POST["controller_mac_pair_$index"]);
    $result = selectController($url, $selectedPairController);
    $controllers = listControllers($url); // Refresh the controller list
} elseif ($action === 'scan_devices_pair' && !empty($selectedPairController)) {
    $devices = scanDevices($url, $selectedPairController);
} elseif ($action === 'save_controller_name' && !empty($selectedPairController)) {
    $newAlias = isset($_POST['controller_name']) ? htmlspecialchars($_POST['controller_name']) : '';
    $result = setSystemAlias($url, $selectedPairController, $newAlias);
    $message = $result ? 'Alias updated successfully!' : 'Error updating alias.';
}

function listControllers($url) {
    $commands = [
        "expect \"Agent registered\"",
        "send -- \"list\\r\"",
        "expect -re {Controller .*}",
        "send -- \"power on\\r\"",
        "expect -re {Changing power on succeeded|Controller .* Powered: yes}"
    ];

    $output = runExpectCommand($url, $commands);

    $controllers = [];
    $seenIds = [];
    foreach (explode("\n", $output) as $line) {
        if (preg_match('/Controller ([0-9A-F:]+) (.+)/', $line, $matches)) {
            $id = $matches[1];
            if (!in_array($id, $seenIds)) {
                $controllers[] = [
                    'id' => $id,
                    'name' => $matches[2]
                ];
                $seenIds[] = $id;
            }
        }
    }
    return $controllers;
}

function runExpectCommand($url, $commands) {
    $expectScript = implode("\n", $commands);
    $expectScript = "spawn sudo /usr/bin/bluetoothctl\n" . $expectScript . "\nsend -- \"quit\\r\"\nexpect eof\n";

    $command = <<<EOL
/usr/bin/expect -c '$expectScript'
EOL;

    $data = json_encode(['command' => $command]);

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data,
        ],
    ];

    $context  = stream_context_create($options);

    $output = @file_get_contents($url, false, $context);

    if ($output === FALSE) {
        $error = error_get_last();
        die('Error occurred while sending request: ' . $error['message']);
    }

    $response = json_decode($output, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Error decoding JSON response');
    }

    if (!isset($response['output'])) {
        die('No output found in response');
    }

    // Debugging output
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'debug_log.txt', $response['output'] . PHP_EOL, FILE_APPEND);

    return $response['output'];
}

function selectController($url, $controller) {
    $commands = [
        "send -- \"select $controller\\r\"",
        "expect \"Controller $controller\""
    ];

    $output = runExpectCommand($url, $commands);

    $controllerName = '';
    if (preg_match('/Controller [0-9A-F:]+ (.+)/', $output, $matches)) {
        $controllerName = $matches[1];
    }

    return [
        'output' => $output,
        'name' => $controllerName
    ];
}

function setSystemAlias($url, $controllerMac, $alias) {
    $commands = [
        "send -- \"select $controllerMac\\r\"",
        "expect \"Controller $controllerMac\"",
        "send -- \"system-alias $alias\\r\"",
        "expect \"Changing $alias succeeded\""
    ];

    $output = runExpectCommand($url, $commands);

    // Debugging: Log the output to a file
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'alias_debug_log.txt', $output . PHP_EOL, FILE_APPEND);

    return strpos($output, "Changing $alias succeeded") !== false;
}

?>


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
        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800,900');
      
        .button {
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
            display: inline-flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            border: none;
            cursor: pointer;
            background-color: #102770;
            color: #ffeba7;
            box-shadow: 0 12px 35px 0 rgba(16, 39, 112, .25);
        }
        .button:hover {
            background-color: #ffeba7;
            color: #102770;
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


    </style>
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
    
    <main style="padding:8px;">
    <h2>System Alias Configuration</h2>
    <form method="post">
        <input type="hidden" name="action" value="list_controllers">
        <button type="submit" class="button">List Controllers</button>
    </form>

    <?php if ($action === 'list_controllers' && empty($controllers)): ?>
        <p>No Bluetooth controllers found.</p>
    <?php endif; ?>

    <?php if ($action === 'list_controllers' && !empty($controllers)): ?>
    <form method="post">
        <?php foreach ($controllers as $index => $controller): ?>
            <label>
                <input type="radio" name="select_controller_index_pair" value="<?php echo $index; ?>">
                <?php echo htmlspecialchars($controller['name']); ?> (<?php echo htmlspecialchars($controller['id']); ?>)
            </label><br>
            <input type="hidden" name="controller_mac_pair_<?php echo $index; ?>" value="<?php echo htmlspecialchars($controller['id']); ?>">
        <?php endforeach; ?>
        <input type="hidden" name="action" value="select_controller_pair">
        <button type="submit" class="button">Select Controller</button>
    </form>
<?php endif; ?>

<?php if ($action === 'select_controller_pair' && isset($selectedPairController)): ?>
    <h4>Selected Controller: <?php echo htmlspecialchars($selectedPairController); ?></h4>

    <form method="post">
        <label for="controller_name">Controller Name:</label>
        <input type="text" id="controller_name" name="controller_name" value="">
        
        <input type="hidden" name="controller_mac_pair" value="<?php echo htmlspecialchars($selectedPairController); ?>">
        <input type="hidden" name="action" value="save_controller_name">
        <button type="submit" class="button" id="saveButton">Save</button>
    </form>
<?php endif; ?>

<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <p id="modalMessage">System-Alias Changed Successfully</p>
    </div>
</div>
</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->
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
   
         <?php include 'footer.html'; ?>
   
 
    


<script>
// Get the modal
var modal = document.getElementById("myModal");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// Show the modal when the "Save" button is clicked
document.getElementById('saveButton').onclick = function() {
    modal.style.display = "block";
};

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>
</body>
</html>
