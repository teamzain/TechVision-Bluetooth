
<?php
@include 'config.php';

session_start();

if (!isset($_SESSION['admin_name'])) {
    header('location:index.php');
}
function redirect($url) {
    header("Location: $url");
    exit;
}
// Handle form submission to add a new user
if (isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']); // Store hashed password
    $cpassword = md5($_POST['cpassword']);
    $user_type = $_POST['user_type'];

    // Check if passwords match
    if ($password !== $cpassword) {
        $error_message = "Passwords do not match!";
    } else {
        $insert_query = "INSERT INTO user_form (name, email, password, user_type) VALUES ('$name', '$email', '$password', '$user_type')";
        if (mysqli_query($conn, $insert_query)) {
            $success_message = "User added successfully!";
        } else {
            $error_message = "Error adding user: " . mysqli_error($conn);
        }
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// Handle form submission to configure the system
if (isset($_POST['configure_system'])) {
    $server_ip = $_POST['server_ip'];
    $allowed_devices = $_POST['allowed_devices'];
    $connected_devices = $_POST['connected_devices'];

    $file_path = 'config.txt';

    // Read existing data from the file
    $data = [];
    if (file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
        $data = json_decode($file_content, true);

        // Ensure $data is an array
        if (!is_array($data)) {
            $data = [];
        }
    }

    // Check if the server IP already exists
    $existing_entry = false;
    foreach ($data as &$entry) {
        if (is_array($entry) && isset($entry['server_ip']) && $entry['server_ip'] == $server_ip) {
            // Update existing entry
            $entry['allowed_devices'] = $allowed_devices;
            $entry['connected_devices'] = $connected_devices;
            $existing_entry = true;
            break;
        }
    }

    if (!$existing_entry) {
        // Add new entry
        $data[] = [
            'server_ip' => $server_ip,
            'allowed_devices' => $allowed_devices,
            'connected_devices' => $connected_devices
        ];
    }

    // Save updated data back to the file
    if (file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT))) {
        $config_success_message = "Configuration saved successfully!";
    } else {
        $config_error_message = "Error saving configuration.";
    }
    redirect($_SERVER['PHP_SELF']);
}

// Fetch users from the database
$query = "SELECT * FROM user_form";
$result = mysqli_query($conn, $query);

$file_path = 'config.txt';

// Initialize an empty array for the configuration data
$config_data = [];

// Check if the file exists and read its content
if (file_exists($file_path)) {
    $config_content = file_get_contents($file_path);
    $config_data = json_decode($config_content, true);

    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON decode error: " . json_last_error_msg();
        $config_data = [];
    }

    // Ensure the data is an array
    if (!is_array($config_data)) {
        $config_data = [];
    }
}

?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal</title>
    <!-- <link rel="stylesheet" href="css/style2.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        span {
            background: crimson;
            color: #fff;
            border-radius: 5px;
            padding: 0 15px;
        }
        .header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            /* box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); */
        }
        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .logo-container img {
            width: 200px; /* Adjust as needed */
            height: 250px;
            margin-top: -40px;
        }
        .logo-container h1 {
            margin: 10px 0 0;
            text-align: center;
            margin-top: -66px;
            background: crimson;
            color: #fff;
            border-radius: 5px;
            padding: 0 15px;
        }
        .header p, .header .btn {
            margin: 0;
        }
        .header p {
            align-self: flex-start;
            font-size: 20px;
            font-style:bold;
        }
        .header .btn {
            background-color: #000000;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            text-decoration: none;
            align-self: flex-start;
        }
        .container {
            max-width: 1000px;
            width: 100%;
            margin-top: 30px;
        }
        .welcome {
            text-align: center;
            font-size: 2rem;
            color: #d50000;
            margin-bottom: 30px;
        }
        .sections {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        @media (min-width: 768px) {
            .sections {
                flex-direction: row;
                justify-content: space-between;
            }
        }
        .section {
            /* background-color: #ffffff; */
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            flex: 1;
        }
        .section h2 {
            font-size: 1.25rem;
            color: #000000;
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            border: 1px solid #dddddd;
            padding: 10px;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
        }
        .action-buttons {
            display: flex;
            justify-content: space-around;
        }
        .action-buttons button {
            background-color: #000000;
            color: #ffffff;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
        }
        .btn-primary {
            display: inline-block;
            padding: 10px 30px;
            font-size: 20px;
            background: #333;
            color: #fff;
            text-transform: capitalize;
            border-radius: 5px;
            transition: background 0.3s;
            text-decoration: none;
        }
        .button-container {
            text-align: center;
            margin-top: 20px;
        }
        .content-table {
    border-collapse: collapse;
    margin: 25px 0;
    margin-left: 10%;
    font-size: 0.9em;
    min-width: 400px;
    border-radius: 5px 5px 0 0;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
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
}

.content-table tbody tr {
    border-bottom: 1px solid #dddddd;
}

.content-table tbody tr:nth-of-type(even) {
    background-color: #f3f3f3;
}

.content-table tbody tr:last-of-type {
    border-bottom: 2px solid #009879;
}

.content-table tbody tr.active-row {
    font-weight: bold;
    color: #009879;
}.action-icons {
            display: flex;
            gap: 10px;
        }
        .action-icons a {
            color: #333;
            text-decoration: none;
        }
        .action-icons a:hover {
            color: #007bff;
        }
        .action-icons i {
            font-size: 18px;
        }
        .btn-primary {
        padding: 10px 30px;
        font-size: 20px;
        background: #333;
        color: #fff;
        text-transform: capitalize;
        border-radius: 5px;
        transition: background 0.3s;
        border: none;
        cursor: pointer;
    }
     /* Modal Styles */
     .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            /* background-color: rgba(0, 0, 0, 0.4); */
            padding-top: 60px;
            box-sizing: border-box;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 5px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
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
       /* Loader Styles */
.loader {
    position: fixed;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none; /* Initially hidden */
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loader .spinner {
    border: 16px solid #f3f3f3; /* Light grey */
    border-top: 16px solid #3498db; /* Blue */
    border-radius: 50%;
    width: 120px;
    height: 120px;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
/* Modal Styles */
.modal {
    display: none; /* Hide modals by default */
    position: fixed;
    z-index: 1;
    margin-top:-20%;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
  /* Black w/ opacity */
}

.modal-content {
    /* background-color: #fefefe; */
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

/* Form Styles */
.form-btn {
    background: #fbd0d9;
    color: crimson;
    text-transform: capitalize;
    font-size: 20px;
    cursor: pointer;
    border: none;
    border-radius: 5px;
    padding: 10px;
    transition: background 0.3s, color 0.3s;
}
.form-btn:hover {
    background: crimson;
    color: #fff;
}
input[type="text"],
input[type="email"],
input[type="password"],
select {
    width: 100%;
    padding: 10px;
    margin: 5px 0;
    border: 1px solid #ddd;
    border-radius: 5px;
}

input[type="text"]:focus,
input[type="email"]:focus,
input[type="password"]:focus,
select:focus {
    border-color: #4CAF50;
    outline: none;
}

/* Loader Styles */
.loader {
    display: none; /* Hide loader by default */
    position: fixed;
    z-index: 2;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.2); /* Black w/ opacity */
    align-items: center;
    justify-content: center;
}

.spinner {
    border: 4px solid rgba(0,0,0,0.1);
    border-left: 4px solid #4CAF50;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Success and Error Messages */
.success {
    color: green;
}

.error {
    color: red;
}


    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <img src="logo.png" alt="TechVision365">
            <h1>TechVision365</h1>
        </div>
        <div style="align-self: flex-start;">
            <p>Tomorrow's Technology</p>
        </div>
        <a href="logout.php" class="btn">Logout</a>
    </div>

    <div class="container">
        <div class="welcome">
            Welcome <span><?php echo $_SESSION['admin_name']; ?></span>
        </div>
 <!-- Add New User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close" data-modal="userModal">&times;</span>
        <h2>Add New User</h2>
        <?php if (isset($success_message)) { echo "<p class='success'>$success_message</p>"; } ?>
        <?php if (isset($error_message)) { echo "<p class='error'>$error_message</p>"; } ?>
        <form action="" method="post">
            <input type="text" name="name" required placeholder="Enter your name">
            <input type="text" name="email" required placeholder="Enter IP address">
            <input type="password" name="password" required placeholder="Enter your password">
            <input type="password" name="cpassword" required placeholder="Confirm your password">
            <select name="user_type">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
            <input type="submit" name="add_user" value="Add User" class="form-btn">
        </form>
    </div>
</div>
<!-- Configure System Modal -->
<div id="configModal" class="modal">
    <div class="modal-content">
        <span class="close" data-modal="configModal">&times;</span>
        <h2>Configure System</h2>
        <?php if(isset($config_success_message)) { echo "<p class='success'>$config_success_message</p>"; } ?>
        <?php if(isset($config_error_message)) { echo "<p class='error'>$config_error_message</p>"; } ?>
        <form id="configForm" action="" method="post">
            <input type="text" id="server_ip" name="server_ip" required placeholder="Server IP Address">
            <button type="button" id="fetchConnectedDevicesBtn" class="form-btn">Fetch Connected Devices</button>
            <input type="text" id="connected_devices" name="connected_devices" required placeholder="Already Connected Devices">
            <input type="text" name="allowed_devices" required placeholder="Allowed Devices">
            <input type="submit" name="configure_system" value="Configure System" class="form-btn">
        </form>
    </div>
</div>

<!-- Loader -->
<div id="loader" class="loader">
    <div class="spinner"></div>
</div>

<div class="sections">
    <div class="section">
        <h2>Admin Portal Credential</h2>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>IP Address</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td class="action-icons">
                            <a href="edit_user.php?id=<?php echo $row['id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="delete_user.php?id=<?php echo $row['id']; ?>" title="Delete"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <div class="button-container">
            <button id="openUserModalBtn" class="btn-primary">Add New User</button>
        </div>
    </div>

    <div class="section">
        <h2>GSM/VoIP Gateway Configuration</h2>
        <h2>System Configuration</h2>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Server IP</th>
                    <th>Allowed Devices</th>
                    <th>Connected Devices</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="configTableBody">
                <?php if (empty($config_data)) { ?>
                    <tr>
                        <td colspan="4">No configuration data available.</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($config_data as $entry) { ?>
                        <?php if (is_array($entry)) { ?>
                            <tr data-server-ip="<?php echo htmlspecialchars($entry['server_ip']); ?>">
                                <td><?php echo htmlspecialchars($entry['server_ip']); ?></td>
                                <td><?php echo htmlspecialchars($entry['allowed_devices']); ?></td>
                                <td class="connected-devices"><?php echo htmlspecialchars($entry['connected_devices']); ?></td>
                                <td class="action-icons">
                                    <!-- <a href="#" title="Refresh" onclick="fetchBluetoothInfo2('<?php echo $entry['server_ip']; ?>', this)"><i class="fas fa-sync-alt"></i></a> -->
                                    <a href="#" title="Delete" onclick="deleteConfig('<?php echo $entry['server_ip']; ?>', this)"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
        <div class="button-container">
            <button id="openConfigModalBtn" class="btn-primary">Configure System</button>
        </div>
    </div>
</div>
<script>
function fetchBluetoothInfo(serverIp, element, showLoader = true) {
    const loader = document.getElementById('loader');

    if (showLoader) {
        loader.style.display = 'flex';
    }

    if (serverIp) {
        fetch(`fetch_connected_device2.php?server_ip=${serverIp}`)
    .then(response => response.text())  // Change this to .text() for debugging
    .then(text => {
        console.log('Server response:', text);  // Log the raw response

        try {
            const data = JSON.parse(text);  // Try parsing the text as JSON

            console.log('fetchBluetoothInfo data:', data); // Debugging
            if (data.success) {
                // Continue with your existing logic here
                const row = document.querySelector(`tr[data-server-ip="${serverIp}"]`);
                if (row) {
                    const connectedDevicesCell = row.querySelector('.connected-devices');
                    const previousCount = connectedDevicesCell.textContent;
                    const newCount = data.connected_devices;

                    if (previousCount != newCount) {
                        connectedDevicesCell.textContent = newCount;
                    }
                }

                const connectedDevicesInput = document.getElementById('connected_devices');
                if (connectedDevicesInput && element) {
                    connectedDevicesInput.value = data.connected_devices;
                }
            } else {
                console.error('Failed to fetch connected devices.');
            }
        } catch (error) {
            console.error('Error parsing JSON response:', error, text);
        }
    })
    .catch(error => {
        console.error('Error fetching Bluetooth info:', error);
    })
    .finally(() => {
        if (showLoader) {
            loader.style.display = 'none'; // Hide the loader
        }
    });

    }
}

function updateConnectedDevices() {
    console.log('Updating connected devices...'); // Debugging
    const rows = document.querySelectorAll('#configTableBody tr[data-server-ip]');
    if (rows.length === 0) {
        console.log('No rows found for update.');
    }
    rows.forEach(row => {
        const serverIp = row.getAttribute('data-server-ip');
        fetchBluetoothInfo(serverIp, null, false);
    });
}

// Set an interval to update the connected devices every 2 seconds
setInterval(updateConnectedDevices, 2000);

// Attach event listener to the fetch button
document.getElementById('fetchConnectedDevicesBtn').addEventListener('click', function() {
    const serverIp = document.getElementById('server_ip').value;
    fetchBluetoothInfo2();
});

// Function to manually fetch connected devices and update the input field
function fetchBluetoothInfo2() {
    const serverIp = document.getElementById('server_ip').value;
    const loader = document.getElementById('loader');

    if (serverIp) {
        loader.style.display = 'flex'; // Show the loader

        fetch(`fetch_connected_devices.php?server_ip=${serverIp}`)
            .then(response => response.json())
            .then(data => {
                console.log('fetchBluetoothInfo2 data:', data); // Debugging
                if (data.success) {
                    document.getElementById('connected_devices').value = data.connected_devices;
                } else {
                    alert('Failed to fetch connected devices.');
                }
            })
            .catch(error => {
                console.error('Error fetching Bluetooth info:', error);
            })
            .finally(() => {
                loader.style.display = 'none'; // Hide the loader
            });
    }
}

// Function to delete a configuration
function deleteConfig(serverIp, element) {
    if (confirm('Are you sure you want to delete this configuration?')) {
        const loader = document.getElementById('loader');
        loader.style.display = 'flex';

        fetch(`delete_config.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ server_ip: serverIp })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Configuration deleted successfully.');
                const row = element.closest('tr');
                row.parentNode.removeChild(row);
            } else {
                alert('Failed to delete configuration.');
            }
        })
        .catch(error => {
            console.error('Error deleting configuration:', error);
        })
        .finally(() => {
            loader.style.display = 'none';
        });
    }
}

// Close modal functionality
document.querySelectorAll('.close').forEach(element => {
    element.onclick = function() {
        const modalId = this.getAttribute('data-modal');
        document.getElementById(modalId).style.display = 'none';
    };
});

// Open modals
document.getElementById('openUserModalBtn').onclick = function() {
    document.getElementById('userModal').style.display = 'block';
};

document.getElementById('openConfigModalBtn').onclick = function() {
    document.getElementById('configModal').style.display = 'block';
};

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};
</script>


</body>
</html>
