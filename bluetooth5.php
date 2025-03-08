

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
$url = 'http://' . $ip_address . '/execute.php';




// Fetch Bluetooth info if total devices are not stored in session
if (!isset($_SESSION['total_devices'])) {
    fetchBluetoothInfo($url);
}

function getAllowedDevices() {
    $file_path = 'config.txt';
    $data = [];
    
    if (file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
        $data = json_decode($file_content, true);
        
        if (!is_array($data)) {
            $data = [];
        }
    }

    $server_ip = isset($_POST['server_ip']) ? $_POST['server_ip'] : '';
    
    foreach ($data as $entry) {
        if (isset($entry['server_ip']) && $entry['server_ip'] == $server_ip) {
            return (int)$entry['allowed_devices'];
        }
    }
    return 0;
}

// Function to get the total number of connected devices
function getTotalConnectedDevices() {
    return isset($_SESSION['total_devices']) ? (int)$_SESSION['total_devices'] : 0;
}

// Fetch Bluetooth info and store in session
function fetchBluetoothInfo($url) {
    $commands = [
        'expect "Agent registered"',
    ];

    $output = runExpectCommand($url, $commands);

    error_log("Bluetooth devices output: " . $output);

    $controllers = parseBluetoothOutput(explode("\n", $output));
    $totalDevices = array_sum(array_map(function($controller) {
        return count($controller['devices']);
    }, $controllers));

    $_SESSION['total_devices'] = $totalDevices;
}

// function parseBluetoothOutput($output) {
//     $controllers = [];
//     $current_controller = null;

//     foreach ($output as $line) {
//         if (preg_match('/Controller ([\w:]+) (.+) \[default\]/', $line, $matches) || 
//             preg_match('/Controller ([\w:]+) (.+)/', $line, $matches)) {
//             if ($current_controller) {
//                 addController($controllers, $current_controller);
//             }
//             $current_controller = [
//                 'mac' => $matches[1],
//                 'name' => $matches[2],
//                 'devices' => []
//             ];
//         }

//         if (preg_match('/Device ([\w:]+) (.+)/', $line, $matches)) {
//             if ($current_controller) {
//                 $current_controller['devices'][] = [
//                     'mac' => $matches[1],
//                     'name' => $matches[2]
//                 ];
//             }
//         }
//     }

//     if ($current_controller) {
//         addController($controllers, $current_controller);
//     }

//     return $controllers;
// }

// function addController(&$controllers, $controller) {
//     foreach ($controllers as &$existing) {
//         if ($existing['mac'] === $controller['mac']) {
//             $existing['devices'] = array_merge($existing['devices'], $controller['devices']);
//             return;
//         }
//     }
//     $controllers[] = $controller;
// }


$action = isset($_POST['action']) ? htmlspecialchars($_POST['action']) : '';

$controllers = [];
$devices = [];
$selectedPairController = isset($_POST['controller_mac_pair']) ? htmlspecialchars($_POST['controller_mac_pair']) : '';
$selectedRemoveController = isset($_POST['controller_mac_remove']) ? htmlspecialchars($_POST['controller_mac_remove']) : '';

if ($action === 'list_controllers') {
    // Fetch controllers only if conditions are met
    $allowed_devices = getAllowedDevices();
    $total_connected_devices = getTotalConnectedDevices();
    
    if ($total_connected_devices >= $allowed_devices) {
        // Show error message in a popup using JavaScript
        echo "<script>alert('Cannot pair device. The number of connected devices exceeds the allowed limit.');</script>";
    } else {
       
        $controllers = listControllers($url);
    }
}


elseif ($action === 'list_controllers_remove') {
    $controllers = listControllers($url);
}
 elseif ($action === 'select_controller_remove' && isset($_POST['select_controller_index_remove'])) {
    $index = intval($_POST['select_controller_index_remove']);
    $selectedRemoveController = htmlspecialchars($_POST["controller_mac_remove_$index"]);
    $output = selectController($url,$selectedRemoveController);
    $controllers = listControllers($url); // Refresh the controller list
} elseif ($action === 'select_controller_pair' && isset($_POST['select_controller_index_pair'])) {
    $index = intval($_POST['select_controller_index_pair']);
    $selectedPairController = htmlspecialchars($_POST["controller_mac_pair_$index"]);
    $output = selectController($url,$selectedPairController);
    $controllers = listControllers($url); // Refresh the controller list
}if ($action === 'scan_devices_pair' && !empty($selectedPairController)) {
    $devices = scanDevices($url,$selectedPairController);
    // $devices now contains the list of devices after scanning
    // You don't need to call listDevices() separately

 // List devices after scanning
} elseif ($action === 'scan_devices_remove' && !empty($selectedRemoveController)) {
    $output = scanPairedDevices($url,$selectedRemoveController);
    $devices = listPairedDevices($url,$output); // List devices after scanning
} elseif ($action === 'pair_device' && isset($_POST['selected_device'])) {
    $selectedDevice = htmlspecialchars($_POST['selected_device']);
    $output = pairDevice($url,$selectedDevice, $selectedPairController); // Pass selected controller to pairDevice function
    if (strpos($output, "Pairing successful") !== false) {
        $pairingSuccessful = true; // Set pairing successful flag
    } else {
        $devices = listDevices($url); // List devices again if pairing failed
    }
} elseif ($action === 'remove_device' && isset($_POST['selected_device'])) {
    $selectedDevice = htmlspecialchars($_POST['selected_device']);
    $output = removeDevice($url,$selectedDevice, $selectedRemoveController); // Pass selected controller to removeDevice function
    if (strpos($output, "removed successfully") !== false) {
        $scanOutput = scanPairedDevices($url, $selectedRemoveController); // Get the output from scanning paired devices
        $devices = listPairedDevices($url, $scanOutput); // Pass both $url and the scanned output
    }
    
} elseif ($action === 'fetch_bluetooth_info') {
    
    $commands = [
        'expect "Agent registered"',
    ];

    // Run the Expect command
    $output = runExpectCommand($url, $commands);

    // Log output for debugging
    error_log("Bluetooth devices output: " . $output);

    // Parse the output to gather unique controller and device information
    $controllers = parseBluetoothOutput(explode("\n", $output));
    $totalDevices = array_sum(array_map(function($controller) {
        return count($controller['devices']);
    }, $controllers));

    $_SESSION['total_devices'] = $totalDevices; // Store the total devices in session
}
// Clear session devices on page load/reload
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    unset($_SESSION['devices']);
}

function parseBluetoothOutput($output) {
    $controllers = [];
    $current_controller = null;

    foreach ($output as $line) {
        // Check for controller information
        if (preg_match('/Controller ([\w:]+) (.+) \[default\]/', $line, $matches) || 
            preg_match('/Controller ([\w:]+) (.+)/', $line, $matches)) {
            if ($current_controller) {
                addController($controllers, $current_controller);
            }
            $current_controller = [
                'mac' => $matches[1],
                'name' => $matches[2],
                'devices' => []
            ];
        }

        // Check for device information
        if (preg_match('/Device ([\w:]+) (.+)/', $line, $matches)) {
            if ($current_controller) {
                $current_controller['devices'][] = [
                    'mac' => $matches[1],
                    'name' => $matches[2]
                ];
            }
        }
    }

    // Add the last controller to the list
    if ($current_controller) {
        addController($controllers, $current_controller);
    }

    return $controllers;
}

function addController(&$controllers, $controller) {
    // Check if the controller already exists (based on MAC address)
    foreach ($controllers as &$existing) {
        if ($existing['mac'] === $controller['mac']) {
            // Merge devices if the controller already exists
            $existing['devices'] = array_merge($existing['devices'], $controller['devices']);
            return; // Controller already exists, skip adding
        }
    }
    $controllers[] = $controller;
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
    file_put_contents('debug_log.txt', $response['output'], FILE_APPEND);

    return $response['output'];
}

function listConnectedDevices($url,$controller) {
    $commands = [
        "send -- \"select $controller\\r\"",
        "expect \"Controller $controller\"",
        "send -- \"devices\\r\"",
        "expect -re {Device [0-9A-F:]+ .*}",
    ];
    $output = runExpectCommand($commands);

    // Parse the output to get devices
    $devices = [];
    foreach (explode("\n", $output) as $line) {
        if (preg_match('/Device ([0-9A-F:]+) (.+)/', $line, $matches)) {
            $devices[] = [
                'id' => $matches[1],
                'name' => $matches[2]
            ];
        }
    }
    return $devices;
}
function listControllers($url) {
    $commands = [
        "expect \"Agent registered\"",
        "send -- \"list\\r\"",
        "expect -re {Controller .*}",
        "send -- \"power on\\r\"",
        "expect -re {Changing power on succeeded|Controller .* Powered: yes}",
        "send -- \"pairable on\\r\"",
        "expect -re {Changing pairable on succeeded|Controller .* pairable: yes}",
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
function selectController($url,$controller) {
    // Define the commands to run in `bluetoothctl`
    $commands = [
        "send -- \"select $controller\\r\"",
        "expect \"Controller $controller\"",
        "send -- \"power on\\r\"",
        "expect -re {Changing power on succeeded|Controller $controller Powered: yes}",
        "send -- \"pairable on\\r\"",
        "expect -re {Changing pairable on succeeded|Controller .* pairable: yes}",
        "send -- \"devices\\r\"",
        "expect -re {Device [0-9A-F:]+ (.+)\\r?}",
    ];

    // Run the commands and capture the output
    $output = runExpectCommand($url,$commands);

    // Parse the output to get paired devices for the selected controller
    $pairedDevices = [];
    $lines = explode("\n", $output);

    // Flag to track if we are in the "devices" section
    $inDevicesSection = false;

    // Iterate through each line of output
    foreach ($lines as $line) {
        // Remove any special formatting
        $line = preg_replace('/\x1B\[[0-9;]*[JKmsu]/', '', $line);

        // Check if we have reached the "devices" section
        if (strpos($line, 'devices') !== false) {
            $inDevicesSection = true;
            continue;
        }

        // If we are in the "devices" section and find a device, add it to the list
        if ($inDevicesSection && preg_match('/Device ([0-9A-F:]+) (.+)/', $line, $matches)) {
            $pairedDevices[] = [
                'id' => $matches[1],
                'name' => $matches[2]
            ];
        }
    }

    // Return the parsed devices or an empty array if no devices found
    return [
        'output' => $output,
        'pairedDevices' => $pairedDevices
    ];
}
function scanPairedDevices($url,$controller) {
    $commands = [
        "send -- \"select $controller\\r\"",
        "expect \"Controller $controller\"",
        "send -- \"paired-devices\\r\"",
        "expect -re {Device [0-9A-F:]+ .*}",
    ];
    $output = runExpectCommand($url,$commands);
    return $output;
}

function listPairedDevices($url,$output) {
    // Parse the output to get devices
    $devices = [];
    foreach (explode("\n", $output) as $line) {
        if (preg_match('/Device ([0-9A-F:]+) (.+)/', $line, $matches)) {
            $devices[] = [
                'id' => $matches[1],
                'name' => $matches[2]
            ];
        }
    }
    return $devices;
}function scanDevices($url,$controller) {
    $commands = [
        "send -- \"select $controller\\r\"",
        "expect \"Controller $controller\"",
        "send -- \"scan on\\r\"",
        "sleep 15", // Scan for 20 seconds
        "send -- \"scan off\\r\"",
        "expect \"Scan off\"",
        "send -- \"devices\\r\"",
        "expect -re {Device [0-9A-F:]+ .*\\r?}",
    ];
    $output = runExpectCommand($url,$commands);

    // Parse the output to get devices
    $devices = [];
    $lines = explode("\n", $output);
    foreach ($lines as $line) {
        // Ignore lines containing 'ManufacturerData', 'RSSI', 'TxPower', 'odalias', or 'UUIDs'
        if (strpos($line, 'ManufacturerData') !== false || 
            strpos($line, 'RSSI') !== false || 
            strpos($line, 'TxPower') !== false || 
            strpos($line, 'odalias') !== false || 
            strpos($line, 'UUIDs') !== false) {
            continue;
        }
        if (preg_match('/Device ([0-9A-F:]+) (.+)/', $line, $matches)) {
            $devices[$matches[1]] = $matches[2]; // Use device ID as key to avoid duplicates
        }
    }

    // Convert the associative array back to an indexed array
    $result = [];
    foreach ($devices as $id => $name) {
        $result[] = [
            'id' => $id,
            'name' => $name
        ];
    }
    
    return $result;
}


function listDevices($url) {
    $commands = [
        "send -- \"devices\\r\"",
        "expect -re {Device [0-9A-F:]+ .*}",
    ];
    $output = runExpectCommand($url,$commands);

    // Parse the output to get devices
    $devices = [];
    foreach (explode("\n", $output) as $line) {
        if (preg_match('/Device ([0-9A-F:]+) (.+)/', $line, $matches)) {
            $devices[] = [
                'id' => $matches[1],
                'name' => $matches[2]
            ];
        }
    }
    return $devices;
}

function pairDevice($url, $device, $selectedController) {
    // Define the Expect commands for pairing
    $commands = [
        "send -- \"select $selectedController\\r\"", // Select the desired controller
        "expect \"Controller $selectedController\"",
        "send -- \"scan on\\r\"", // Start scanning
        "expect \"Discovery started\"",
        "sleep 1", // Adjust sleep time as necessary to allow device discovery
        "send -- \"scan off\\r\"", // Stop scanning
        "expect \"Discovery stopped\"",
        // Check if device is found
        "send -- \"pair $device\\r\"", // Attempt pairing
        "expect -re {Attempting to pair|Pairing successful|Failed to pair}",
        "expect -re {ServicesResolved: yes|Paired: yes}",
        "sleep 5", // Add a small delay for stability
    ];

    // Execute all commands
    $output = runExpectCommand($url, $commands);

    // Check the final status in the output
    if (strpos($output, "Pairing successful") !== false) {
       
        // Include 'chan_mobile.php' if needed
        include 'chan_mobile.php';

        return "Pairing successful with device $device. Module reloaded.";
     
    } elseif (strpos($output, "Failed to pair") !== false) {
        return "Failed to pair with device $device. Please try again.";
    } elseif (strpos($output, "Attempting to pair") !== false) {
        return "Pairing process initiated for device $device. Waiting for confirmation.";
    } elseif (strpos($output, "ServicesResolved: yes") !== false && strpos($output, "Paired: yes") !== false) {
        return "Pairing successful with device $device.";
    } else {
        return "Unknown pairing status for device $device. Please check and try again.";
    }
}

function removeDevice($url, $device, $selectedController) {
    // Define the Expect commands for removing the device
    $commands = [
        "send -- \"select $selectedController\\r\"", // Select the desired controller
        "expect \"Controller $selectedController\"",
        "send -- \"remove $device\\r\"", // Remove the device
        "expect -re {Device has been removed|not available}",
        "sleep 1", // Add a small delay for stability
    ];

    // Execute all commands
    $output = runExpectCommand($url, $commands);

    if (strpos($output, "Device has been removed") !== false) {
        // Unload and reload the module after successful device removal
    

        // Include 'chan_mobile.php' if needed
        include 'chan_mobile.php';

        return "Device $device removed successfully. Module reloaded.";
    } else {
        return "Failed to remove device $device. It may not be available.";
    }
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
  
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800,900');
        /* body {
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
            font-size: 15px;
            line-height: 1.7;
            color: #1f2029;
            background-color: #fff;
            background-image: url('https://assets.codepen.io/1462889/back-page.svg');
            background-position: center;
            background-repeat: no-repeat;
            background-size: 101%; */
        .button {
    position: relative;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    font-size: 15px;
    line-height: 2;
    height: 50px; /* Set a minimum height to maintain button size */
    transition: all 200ms linear;
    border-radius: 4px;
    width: 240px;
    letter-spacing: 1px;
    display: inline-flex; /* Display buttons inline */
    margin-right: 10px; /* Adjust margin between buttons */
    justify-content: center;
    align-items: center;
    text-align: center;
    border: none;
    cursor: pointer;
    background-color: #102770;
    color: #ffeba7;
    box-shadow: 0 12px 35px 0 rgba(16, 39, 112, .25);
    overflow: hidden; /* Hide overflow text if it exceeds button height */
    padding: 15px; /* Add padding inside the button */
}

.button:hover {
    background-color: #ffeba7;
    color: #102770;
}

        .form-container {
            margin-bottom: 20px; /* Add some bottom margin for spacing */
        }
        .form-container form {
            display: inline-block; /* Ensure forms display in a line */
            margin-right: 20px; /* Adjust margin between forms */
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

        .d-1 {
  --c: #1095c1; /* the color */
  --b: .1em;    /* border length*/
  --d: 20px;    /* the cube depth */
  --h: 1.2em;   /* the height */
  
  --_s: calc(var(--d) + var(--b));

  line-height: var(--h);
  color: #0000;
  text-shadow: 
    0 calc(-1*var(--_t,0em)) var(--c), 
    0 calc(var(--h) - var(--_t,0em)) #fff;
  border: solid #0000;
  overflow: hidden;
  border-width: var(--b) var(--b) var(--_s) var(--_s);
  background:
    linear-gradient(var(--c) 0 0) 100% 100%
     /101% var(--_p,0%) no-repeat,
    conic-gradient(at left var(--d)  bottom var(--d),
      #0000 90deg, rgb(255 255 255 /0.3) 0 225deg,rgb(255 255 255 /0.6) 0) border-box,
    conic-gradient(at left var(--_s) bottom var(--_s),
      #0000 90deg,var(--c) 0) 0 100%/calc(100% - var(--b)) calc(100% - var(--b))  border-box;
  transform: translate(calc(var(--d)/-1),var(--d));
  clip-path: 
    polygon(
     var(--d) 0%, 
     var(--d) 0%, 
     100% 0%, 
     100% calc(100% - var(--d)),
     100% calc(100% - var(--d)),
     var(--d) calc(100% - var(--d))
    );
  transition: 0.5s;
}
.d-1:hover {
  transform: translate(0,0);
  clip-path: 
    polygon(
     0% var(--d), 
     var(--d) 0%, 
     100% 0%, 
     100% calc(100% - var(--d)), 
     calc(100% - var(--d)) 100%, 
     0% 100%
   );
  --_t: var(--h);
  --_p: 105%;
}

.d-2 {
  --c: #CC333F; /* the color */
  --b: .1em;    /* border length*/
  --d: 20px;    /* the cube depth */
  --h: 1.2em;   /* the height */
  
  --_s: calc(var(--d) + var(--b));
  color: #0000;
  text-shadow: 
    0 calc(-1*var(--_t,0em)) var(--c), 
    0 calc(var(--h) - var(--_t,0em)) #fff;
  border: solid #0000;
  overflow: hidden;
  border-width: var(--b) var(--_s) var(--_s) var(--b);
  background:
    linear-gradient(var(--c) 0 0) -1px 100%
     /101% var(--_p,0%) no-repeat,
    conic-gradient(from -90deg at right var(--d)  bottom var(--d),
      #0000 90deg, rgb(255 255 255 /0.3) 0 225deg,rgb(255 255 255 /0.6) 0) border-box,
    conic-gradient(at right var(--_s) bottom var(--_s),
      var(--c) 270deg,#0000 0) 100% 100%/calc(100% - var(--b)) calc(100% - var(--b))  border-box;
  transform: translate(var(--d),var(--d));
  clip-path: 
    polygon(
      0% 0%,
      calc(100% - var(--d)) 0%,
      calc(100% - var(--d)) 0%,
      calc(100% - var(--d)) calc(100% - var(--d)),
      0 calc(100% - var(--d)),
      0 calc(100% - var(--d))
    );
  transition: 0.5s;
}
.d-2:hover {
  transform: translate(0,0);
  clip-path: 
    polygon(
      0% 0%,
      calc(100% - var(--d)) 0%,
      100% var(--d),
      100% 100%,
      var(--d) 100%,
      0 calc(100% - var(--d))
    );
  --_t: var(--h);
  --_p: 105%;
}

/* CSS */
.button-43 {
  background-image: linear-gradient(-180deg, #37AEE2 0%, #1E96C8 100%);
  border-radius: .5rem;
  box-sizing: border-box;
  color: #FFFFFF;
  display: flex;
  font-size: 16px;
  justify-content: center;
  padding: 1rem 1.75rem;
  text-decoration: none;
  width: 100%;
  border: 0;
  cursor: pointer;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
}

.button-43:hover {
  background-image: linear-gradient(-180deg, #1D95C9 0%, #17759C 100%);
}

@media (min-width: 768px) {
  .button-43 {
    padding: 1rem 2rem;
  }
}
.button-75 {
  background-image: linear-gradient(135deg, #f34079 40%, #fc894d);
  border-radius: .5rem;
  box-sizing: border-box;
  color: #fff;
  display: flex;
  font-size: 16px;
  justify-content: center;
  padding: 1rem 1.75rem;
  text-decoration: none;
  width: 100%;
  border: 0;
  cursor: pointer;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
}

.button-75:hover {
  background-image: linear-gradient(-180deg, #1D95C9 0%, #17759C 100%);
}

@media (min-width: 768px) {
  .button-43 {
    padding: 1rem 2rem;
  }
}


/* body {
  height: 100vh;
  margin: 0;
  display: grid;
  grid-template-columns: auto auto;
  gap: 20px;
  place-content: center;
  align-items: center;
}
h3 {
  font-family: system-ui, sans-serif;
  font-size: 3rem;
  margin:0;
  cursor: pointer;
  padding: 0 .1em;
} */
#loader {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            
            z-index: 1000;
        }

        .spinner {
            border: 12px solid #f3f3f3;
            border-radius: 50%;
            border-top: 12px solid blue;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .toggle-button {
            width: 60px;
            height: 30px;
            background-color: #ccc;
            border-radius: 15px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .toggle-button .toggle-circle {
            width: 28px;
            height: 28px;
            background-color: white;
            border-radius: 50%;
            position: absolute;
            top: 1px;
            left: 1px;
            transition: left 0.3s;
        }

        .toggle-button.active {
            background-color: #4CAF50;
        }

        .toggle-button.active .toggle-circle {
            left: 31px;
        }

        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border: 1px solid #ccc;
            max-width: 600px;
            margin-top: 20px;
            white-space: pre-wrap;
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

    <div id="loader" style="display: none;">
        <div class="spinner"></div>
    </div>
<div class="form-container">
<form method="POST">

        <input type="hidden" name="action" value="fetch_bluetooth_info">
        <button type="submit" class="button-43">Show Adapter & Connected Devices</button>
    </form>
    <br><br>
    <form method="post">
        <input type="hidden" name="action" value="list_controllers">
        <input type="hidden" name="server_ip" value="<?php echo htmlspecialchars($ip_address); ?>">
        <button type="submit" class="button-43" onclick="connectDevice()">Pair a Device</button>
    </form>
    <br><br>

    <form method="post">
        <input type="hidden" name="action" value="list_controllers_remove">
        <button type="submit" class="button-75"><span class="text">Remove a Device</span></button>
    </form>
   
</div>




<?php if ($action === 'list_controllers' && empty($controllers)): ?>
    <p>No Bluetooth controllers found.</p>
<?php endif; ?>

<?php if ($action === 'list_controllers_remove' && empty($controllers)): ?>
    <p>No Bluetooth controllers found.</p>
<?php endif; ?>

<?php if ($action === 'list_controllers_remove' && !empty($controllers)): ?>
    <form method="post">
        <?php foreach ($controllers as $index => $controller): ?>
            <label>
                <input type="radio" name="select_controller_index_remove" value="<?php echo $index; ?>">
                <?php echo htmlspecialchars($controller['name']); ?> (<?php echo htmlspecialchars($controller['id']); ?>)
            </label><br>
            <input type="hidden" name="controller_mac_remove_<?php echo $index; ?>" value="<?php echo htmlspecialchars($controller['id']); ?>">
        <?php endforeach; ?>
        <input type="hidden" name="action" value="select_controller_remove">
        <button type="submit" class="button">Select Controller</button>
    </form>
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
    <?php $result = selectController($url,$selectedPairController); ?>
    <?php $pairedDevices = $result['pairedDevices']; ?>

    <h2>Already Connected Devices</h2>
    <form method="post">
        <?php if (!empty($pairedDevices)): ?>
            <?php foreach ($pairedDevices as $device): ?>
                <p>
                    <?php echo htmlspecialchars($device['name']); ?> (<?php echo htmlspecialchars($device['id']); ?>)
                </p>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No device connected to the controller.</p>
        <?php endif; ?>
        <input type="hidden" name="controller_mac_pair" value="<?php echo htmlspecialchars($selectedPairController); ?>">
        <input type="hidden" name="action" value="scan_devices_pair">
        <button type="submit" class="button">Scan For Devices</button>
    </form>
<?php endif; ?>


<?php if ($action === 'select_controller_remove' && isset($selectedRemoveController)): ?>
    <h4>Selected Controller: <?php echo htmlspecialchars($selectedRemoveController); ?></h4>
    <?php $result = selectController($url,$selectedRemoveController); ?>
    <?php $pairedDevices = $result['pairedDevices']; ?>

    <h2>Paired Devices</h2>
    <form method="post">
        <?php if (!empty($pairedDevices)): ?>
            <?php foreach ($pairedDevices as $device): ?>
                <label>
                    <input type="radio" name="selected_device" value="<?php echo htmlspecialchars($device['id']); ?>">
                    <?php echo htmlspecialchars($device['name']); ?> (<?php echo htmlspecialchars($device['id']); ?>)
                </label><br>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No device connected to the controller.</p>
        <?php endif; ?>
        <input type="hidden" name="action" value="remove_device">
        <input type="hidden" name="controller_mac_remove" value="<?php echo htmlspecialchars($selectedRemoveController); ?>">
        <button type="submit" class="button">Remove Device</button>
    </form>
<?php endif; ?>

<?php if ($action === 'scan_devices_remove' && isset($devices) && !empty($devices)): ?>
    <h2>Devices</h2>
    <form method="post">
        <?php foreach ($devices as $device): ?>
            <label>
                <input type="radio" name="selected_device" value="<?php echo htmlspecialchars($device['id']); ?>">
                <?php echo htmlspecialchars($device['name']); ?> (<?php echo htmlspecialchars($device['id']); ?>)
            </label><br>
        <?php endforeach; ?>
        <input type="hidden" name="action" value="remove_device">
        <input type="hidden" name="controller_mac_remove" value="<?php echo htmlspecialchars($selectedRemoveController); ?>">
        <button-43 type="submit" class="button">Remove Device</button>
    </form>
<?php endif; ?>

<?php if ($action === 'scan_devices_pair' && isset($devices) && !empty($devices)): ?>
    <h2>Devices</h2>
    <form method="post">
        <?php foreach ($devices as $device): ?>
            <label>
                <input type="radio" name="selected_device" value="<?php echo htmlspecialchars($device['id']); ?>">
                <?php echo htmlspecialchars($device['name']); ?> (<?php echo htmlspecialchars($device['id']); ?>)
            </label><br>
        <?php endforeach; ?>
        <input type="hidden" name="action" value="pair_device">
        <input type="hidden" name="controller_mac_pair" value="<?php echo htmlspecialchars($selectedPairController); ?>">
        <button type="submit" class="button">Pair Device</button>
    </form>
<?php endif; ?>
<?php if ($action === 'pair_device' && isset($output)): ?>
<br><br>
    <pre style="color: <?php echo (strpos($output, 'Pairing successful') !== false) ? 'green' : 'red'; ?>">
    <div class="toggle-button" id="toggleBtn">
        <div class="toggle-circle"></div>
    </div>
        <?php echo htmlspecialchars($output); ?>
    </pre>
<?php endif; ?>

<?php if ($action === 'remove_device' && isset($output)): ?>
    <br><br>
    <pre style="color: <?php echo (strpos($output, 'removed successfully') !== false) ? 'green' : 'red'; ?>">
    <div class="toggle-button" id="toggleBtn">
        <div class="toggle-circle"></div>
    </div>
        <?php echo htmlspecialchars($output); ?>
    </pre>
<?php endif; ?>



<?php if ($action === 'fetch_bluetooth_info' && !empty($controllers)): ?>
    <table class="content-table">
        <thead>
            <tr>
                <th>Controller</th>
                <th>Devices</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($controllers as $controller) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($controller['mac']) . " - " . htmlspecialchars($controller['name']); ?></td>
                    <td>
                        <?php if (!empty($controller['devices'])) {
                            foreach ($controller['devices'] as $device) {
                                echo htmlspecialchars($device['mac']) . " - " . htmlspecialchars($device['name']) . "<br>";
                            }
                        } else {
                            echo "No device connected";
                        } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php endif; ?>
			
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all forms
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                // Show loader
                document.getElementById('loader').style.display = 'block';
            });
        });

        // Hide loader when output is displayed
        <?php if ($action): ?>
        document.getElementById('loader').style.display = 'none';
        <?php endif; ?>
    });
    </script>
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
       <script>
        const toggleBtn = document.getElementById('toggleBtn');
        const outputEl = document.getElementById('output');

        // Toggle button click event
        toggleBtn.addEventListener('click', () => {
            toggleBtn.classList.toggle('active');
            let command = toggleBtn.classList.contains('active') 
                ? 'module load chan_mobile.so' 
                : 'module unload chan_mobile.so';

            // Prepare the data to send
            const data = new FormData();
            data.append('command', command);

            // Make an HTTP POST request to execute the command
            fetch('module.php', {  // The current script will handle the POST request
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(result => {
                outputEl.textContent = JSON.stringify(result, null, 2); // Display the output in <pre>
            })
            .catch(error => {
                outputEl.textContent = 'Error: ' + error; // Display any errors
            });
        });
    </script>
         <script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
   
</body>
</html>

   
 
    

