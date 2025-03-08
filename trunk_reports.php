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

// Function to list trunks from the asterisk database
function trunkbalance_listtrunk() {
    global $execute_url, $db_user, $db_pass, $db_host, $db_name;

    $db_query_command = "mysql -u $db_user -p'$db_pass' -h $db_host -e 'USE $db_name; SELECT name FROM trunks;'";
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
 
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    .modal-custom {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
   
    }

    .modal__container {
      background-color: white;
      border-radius: 15px;
      box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
      padding: 20px;
      max-width: 800px;
      width: 100%;
    }

    .modal__featured {
      text-align: center;
      margin-bottom: 20px;
    }

    .modal__product {
      max-width: 100%;
    }

    .modal__content h2 {
      color: #5469d4;
      text-decoration: none;
    }

    .select, input[type="date"] {
      width: 100%;
    }

    .button {
      background-color: #5469d4;
      color: white;
      font-weight: 600;
      width: 100%;
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
     
<div class="modal-custom">
  <div class="modal__container">
    <div class="modal__featured">
      <div class="modal__circle"></div>
      <!-- <img src="database.png" class="modal__product" /> -->
    </div>
    <div class="modal__content">
      <h2>Check Report</h2>
      <form method="post" action="report_process.php">
        <div class="mb-3">
          <label for="selected_trunk" class="form-label">Select Trunk:</label>
          <select name="selected_trunk" class="form-select" required>
            <?php
            $trunkList = trunkbalance_listtrunk();
            foreach ($trunkList as $trunk) {
              echo "<option value='{$trunk['name']}'>{$trunk['name']}</option>";
            }
            ?>
          </select>
        </div>

        <h2>TimeFrame</h2>
        <div class="mb-3">
          <label for="start_date" class="form-label">Start Date:</label>
          <input type="date" name="start_date" id="startDate" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="end_date" class="form-label">End Date:</label>
          <input type="date" name="end_date" id="endDate" class="form-control" required>
        </div>

        <h2>Shortcuts</h2>
        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
          <button type="button" class="btn btn-primary me-md-2" onclick="setDateRange('today')">Today</button>
          <button type="button" class="btn btn-primary me-md-2" onclick="setDateRange('thisWeek')">This Week</button>
          <button type="button" class="btn btn-primary me-md-2" onclick="setDateRange('thisMonth')">This Month</button>
          <button type="button" class="btn btn-primary" onclick="setDateRange('lastThreeMonths')">Last Three Months</button>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn button">Generate Reports</button>
        </div>
      </form>
    </div>
  </div>
</div>
        </main>
            

    



  
  </section>

<script>
    function setDateRange(shortcut) {
        const today = new Date();
        let startDate, endDate;

        switch (shortcut) {
            case 'today':
                startDate = today.toISOString().split('T')[0];
                endDate = startDate;
                break;
            case 'thisWeek':
                startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() - today.getDay()).toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'thisMonth':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
                break;
            case 'lastThreeMonths':
                startDate = new Date(today.getFullYear(), today.getMonth() - 2, 1).toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            default:
                break;
        }

        document.getElementById('startDate').value = startDate;
        document.getElementById('endDate').value = endDate;
    }

    function showPopup(message) {
    document.getElementById('popupMessage').textContent = message;
    document.getElementById('popup').style.display = 'flex';
}

function closePopup() {
    document.getElementById('popup').style.display = 'none';
}
</script>
<script src="index.js"></script>
  <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('fetch_device.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-connected-devices').textContent = data.total_connected_devices;
                })
                .catch(error => console.error('Error fetching connected devices:', error));
        });
    </script>

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
 
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
