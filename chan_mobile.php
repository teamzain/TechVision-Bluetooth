<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$commands = [
    'expect "Agent registered"',
];

// Run the Expect command
$output = runExpectCommand2($url, $commands);

// Log output for debugging
error_log("Bluetooth devices output: " . $output);

// Parse the output to gather unique controller and device information
$controllers = parseBluetoothOutput2(explode("\n", $output));

// Save the parsed data to a file on the FreePBX server
saveToFileOnFreePBX($controllers, $ip_address);

// Clear session devices on page load/reload
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    unset($_SESSION['devices']);
}

function parseBluetoothOutput2($output) {
    $controllers = [];
    $current_controller = null;

    foreach ($output as $line) {
        // Check for controller information
        if (preg_match('/Controller ([\w:]+) (.+) \[default\]/', $line, $matches) || 
            preg_match('/Controller ([\w:]+) (.+)/', $line, $matches)) {
            if ($current_controller) {
                addController2($controllers, $current_controller);
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
        addController2($controllers, $current_controller);
    }

    return $controllers;
}

function addController2(&$controllers, $controller) {
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

function runExpectCommand2($url, $commands) {
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
function cleanString($string) {
    // Remove carriage return characters
    return str_replace("\r", '', $string);
}

function saveToFileOnFreePBX($controllers, $ip_address) {
    $url = 'http://' . $ip_address . '/save_chan_mobile2.php'; // Update the path as needed

    $fileContent = "[general]\n";
    $fileContent .= "interval=20\n\n";

    foreach ($controllers as $controller) {
        $controllerName = cleanString(htmlspecialchars($controller['name'], ENT_QUOTES, 'UTF-8'));
        $controllerMac = cleanString(htmlspecialchars($controller['mac'], ENT_QUOTES, 'UTF-8'));
        
        $fileContent .= "[adapter]\n";
        $fileContent .= "id=" . $controllerName . "\n";
        $fileContent .= "address=" . $controllerMac . "\n\n";

        foreach ($controller['devices'] as $device) {
            // Set port to 8 if the device name starts with 'iphone', otherwise 4
            $port = (stripos($device['name'], 'iphone') === 0) ? 8 : 4;

            $deviceName = cleanString(htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8'));
            $deviceMac = cleanString(htmlspecialchars($device['mac'], ENT_QUOTES, 'UTF-8'));
            
            $fileContent .= "[{$deviceName}]\n";
            $fileContent .= "context=from-mobile\n";
            $fileContent .= "address=" . $deviceMac . "\n";
            $fileContent .= "adapter=" . $controllerName . "\n";
            $fileContent .= "port={$port}\n\n";
        }
    }

    $data = http_build_query(['fileContent' => $fileContent]);

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => $data,
        ],
    ];

    $context  = stream_context_create($options);

    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        $error = error_get_last();
        die('Error occurred while sending request: ' . $error['message']);
    }
}

 
?>

