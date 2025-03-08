
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
  
      <h1 style="text-align: center;">Mobile Status</h1>
    <div style="text-align: center;">
        <button class="button" onclick="showData()">Show Devices</button>
    </div>
    <div id="dataContainer"></div>
			
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
		   
		
		  
   
 
    <script>
        function showData() {
            fetch('process.php', {
                method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
                // Display the data as an HTML table
                document.getElementById('dataContainer').innerHTML = data;
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
