<?php
session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Get the selected device ID from the form
    $device_id = isset($_POST['device']) ? trim($_POST['device']) : '';

    if (empty($device_id)) {
        die('No device selected');
    }

    // URL of the FreePBX server's execute.php script
    $url = 'http://' . $ip_address . '/execute.php';

    // Construct the command to test the demo call
    $command = 'asterisk -rx "channel originate mobile/' . $device_id . '/959 application MusicOnHold"';

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
        die('Error occurred while executing the command');
    }

    // Decode the JSON response
    $response = json_decode($output, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Error decoding JSON response');
    }

    $message = '<div class="message-box success"><i class="bx bx-check-circle"></i> Demo call initiated successfully for device: ' . htmlspecialchars($device_id) . '</div>';
}
?>
 
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
       body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #fff;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    text-align: center;
 /* Optional: add background color to body */
}

.container {
    background-color: rgba(0, 0, 0, 0.7);
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    max-width: 400px;
    width: 100%;
    margin: 0 auto; /* Center container horizontally */
}

h1 {
    font-size: 24px;
    margin-bottom: 20px;
}

.message-box {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #4CAF50; /* Default to success color */
    color: white;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 18px;
}

.message-box.success {
    background-color: #4CAF50;
}

.message-box.error {
    background-color: #f44336; /* Add error color */
}

.message-box i {
    margin-right: 10px;
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

.bx-phone, .bx-x {
    margin-right: 8px;
}

/* Responsive styles */
@media (max-width: 768px) {
    .container {
        padding: 20px;
        margin: 10px;
    }

    h1 {
        font-size: 20px;
    }

    .message-box {
        font-size: 16px;
    }

    .button {
        padding: 8px 16px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 15px;
        margin: 5px;
    }

    h1 {
        font-size: 18px;
    }

    .message-box {
        font-size: 14px;
    }

    .button {
        padding: 6px 12px;
        font-size: 12px;
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
    <div class="container">
        <h1>Test Demo Call</h1>
        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>
        <form method="POST" action="test_call.php">
            <input type="hidden" name="device" value="<?php echo htmlspecialchars($device_id); ?>">

            <a href="demo_call.php" class="button"><i class="
bx bx-arrow-back"></i> Back</a>
        </form>
        <br><br>
        <form method="POST" action="hangup_call.php">
            <button type="submit" class="button"><i class="bx bx-x"></i> Hangup Call</button>
        </form>
    </div>
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


