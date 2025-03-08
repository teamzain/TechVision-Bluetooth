
<?php include 'loader.html'; ?>
<?php

@include 'config.php';

session_start();

if(!isset($_SESSION['user_name'])){
   header('location:index.php');
   exit(); 
}?>
 
 <html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>TechVision365</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@700&display=swap" rel="stylesheet">
    <!-- Icon Fonts -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <!-- External Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="style.css">
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
      <span class="text">TechVision365 <span style="font-size: smaller;">Inc</span></span>

    </div>
    <!-- <div id="google_translate_element">

    </div> -->
    
    <main style="padding:8px;">
        <h1>Analytics</h1>
        <!-- Analyses -->
        <div class="analyse">
            <div class="sales">
                <div class="status">
                    <div class="info">
                        <a href="searchrecords.php" style="text-decoration: none; color:black;"><h3>Total Connected Devices</h3>
                        <i class='bx bxs-calendar-check' ></i>     <h1 id="total-connected-devices"></h1></a>
                        
                    </div  > 
                    <div>
                    
                    <h3 ></h3>
      
                    </div>

                    <!-- <div class="progresss">
                        <svg>
                        <circle cx="38" cy="38" r="36" stroke-dasharray="227" stroke-dashoffset="<?php echo 227 - ($percentageChange * 2.27); ?>"></circle>
                </svg>
                <div class="percentage">
                    <p></p>
                        </div>
                    </div> -->
                </div>
                </div>
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
 
</body>
</html>
