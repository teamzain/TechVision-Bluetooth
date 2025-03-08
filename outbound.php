
<?php

@include 'config.php';

session_start();

if(!isset($_SESSION['user_name'])){
   header('location:index.php');
}?>
 
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
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-container {
            max-width: 600px;
            width: 100%;
            margin: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-container h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
            text-align: center;
        }
        .form-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-container textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            height: 200px;
            resize: vertical;
        }
        .form-container input[type="submit"] {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .form-container input[type="submit"]:hover {
            background-color: #45a049;
        }
        /* Basic styles for the popup */
        #popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            z-index: 1000;
        }
        .popup-tick {
            color: green;
            font-size: 48px;
            margin-bottom: 10px;
        }
        .popup-message {
            font-size: 18px;
            color: #333;
        }
        @media (max-height: 700px) {
            body {
                align-items: flex-start;
                padding-top: 20px;
            }
        }
    </style>
   </head>
<body>
<!-- <form method="post">
        <button type="submit">Empty All Tables</button>
    </form> -->
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
        <div class="iocn-link">
          <a href="#">
          <i class='bx bx-network-chart'></i>



            <span class="link_name">Add Trunk</span>
          </a>
          <i class='bx bxs-chevron-down arrow' ></i>
        </div>
        <ul class="sub-menu">
     
          <li><a href="sip_server.php">Trunk With Sip Server
          </a></li>
          <li><a href="trunk.php">Trunk With Mobile <br> Devices</a></li>
        
          
         
        </ul>
      </li>
      <li>
        <a href="outbound.php">
        <i class='bx bx-directions'></i>


          <span class="link_name">Outbound Routes</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="outbound.php">Outbound Routes</a></li>
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
   
    <div class="form-container" style="margin-left:20%;">
        <h1>Extension Configuration</h1>
        <form id="extensionForm" action="update_extension.php" method="POST">
            <label for="extensionConfig">Extension Configuration:</label>
            <textarea id="extensionConfig" name="extensionConfig" required></textarea>
            <input type="submit" value="Save Extension Configuration">
        </form>
    </div>

    <div id="popup">
        <div class="popup-tick">✔️</div>
        <div class="popup-message">Extension configuration updated successfully.</div>
    </div>
    </main>
            

    



  
            </section>
            <?php include 'footer.html'; ?>
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
           
       
          
    <script>
        // Show the popup if there's a success status in the URL query parameters
        function showPopup() {
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'success') {
                var popup = document.getElementById('popup');
                popup.style.display = 'block';
                setTimeout(() => {
                    popup.style.display = 'none';
                }, 3000); // Popup will be visible for 3 seconds
            }
        }

        // Call the showPopup function when the page loads
        window.onload = showPopup;
    </script>
</body>
</html>
