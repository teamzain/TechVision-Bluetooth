<?php
session_start();

// Generate the unique file for the current user
$session_id = session_id();
$file = __DIR__ . '/ip_addresses/' . $session_id . '_ip_address.txt';

if (!file_exists($file)) {
    die('IP address file not found. Please enter the IP address first.');
}

$ip_address = trim(file_get_contents($file));

if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
    die('Invalid IP address in file');
}

// URL of the FreePBX server's execute.php script
$url = 'http://' . $ip_address . '/execute.php'; // Use the IP address from the file
$command = 'asterisk -rx "mobile show devices"';

// Prepare the data
$data = json_encode(['command' => $command]);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];

// Create the context for the HTTP request
$context  = stream_context_create($options);

// Send the request and get the result
$output = file_get_contents($url, false, $context);

if ($output === FALSE) {
    die('Error occurred');
}

// Decode the JSON response
$response = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die('Error decoding JSON response');
}

// Explode the output by line
$lines = explode("\n", trim($response['output']));

// Extract device IDs from the output
$devices = [];
foreach ($lines as $line) {
    // Skip the header line and any empty lines
    if (strpos($line, 'ID') !== false || trim($line) === '') {
        continue;
    }

    // Split the line by spaces and take the first element as the ID
    $columns = preg_split('/\s+/', $line);
    $id = $columns[0]; // First column is the ID
    $devices[] = $id;
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
    <!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://translate.google.com/translate_static/css/translateelement.css">
  -->
    
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
	 <style>
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800,900');
        body {
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
            font-size: 15px;
            line-height: 1.7;
            color: #1f2029;
            background-color: #fff;
            background-image: url('https://assets.codepen.io/1462889/back-page.svg');
            background-position: center;
            background-repeat: no-repeat;
            background-size: 101%;
        }
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
}#device {
    width: 100%;
    max-width: 400px; /* Adjust the max width as needed */
    padding: 10px;
    border: 2px solid #009879; /* Border color to match your table style */
    border-radius: 5px;
    background-color: #ffffff;
    color: #333333;
    font-size: 1em;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Add subtle shadow for depth */
    appearance: none; /* Remove default arrow for custom styling */
    -webkit-appearance: none; /* For Safari */
    -moz-appearance: none; /* For Firefox */
}

#device:focus {
    outline: none; /* Remove default outline on focus */
    border-color: #005f4e; /* Darken border on focus */
    box-shadow: 0 0 5px rgba(0, 128, 0, 0.5); /* Add a subtle glow on focus */
}

#device::-ms-expand {
    display: none; /* Hide default arrow in Internet Explorer */
}

.select-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
    max-width: 400px; /* Match this with the select width */
}

.select-wrapper::after {
    content: '\25BC'; /* Custom arrow (▼) */
    font-size: 1em;
    color: #009879; /* Color matching your theme */
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none; /* Prevent arrow from blocking clicks */
}

#device option[disabled] {
    color: #888888; /* Style the "Select a device" option */
    font-weight: bold;
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


            <span class="link_name">Setup</span>
          </a>
          <i class='bx bxs-chevron-down arrow' ></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="#">Setup</li>
          <li><a href="bluetooth5.php">Configure Bluetooth
          </a></li>
          <li><a href="system_alias.php">Setup System Alias</a></li>
        
          
         
        </ul>
      </li>
      <li>
        <a href="check_device.php">
        <i class='bx bx-mobile' ></i>
          <span class="link_name">Check Device</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="check_device.php">Check Device</a></li>
        </ul>
      </li>
      <li>
        <a href="trunk_management.php">
        <i class='bx bx-server' ></i>
          <span class="link_name">Trunk Management</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="trunk_management.php">Trunk Management</a></li>
        </ul>
      </li>
      <li>
        <a href="trunk_reports.php">
        <i class='bx bx-data' ></i>
          <span class="link_name">Trunk Reports</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="trunk_reports.php">Trunk Reports
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
    <form method="POST" action="test_call.php">
        <label for="device">Select Device:</label>
        <select name="device" id="device">
        <option value="" disabled selected>----Select a device----</option>
            <?php foreach ($devices as $device): ?>
                <option value="<?php echo htmlspecialchars($device); ?>"><?php echo htmlspecialchars($device); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button"  type="submit">Test Demo Call</button>
    </form>
    	
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
		   
		
		  
</body>
</html>
