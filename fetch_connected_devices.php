<?php
header('Content-Type: application/json');

if (isset($_GET['server_ip'])) {
    $ip_address = $_GET['server_ip'];
    $url = 'http://' . $ip_address . '/execute.php';

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

    // Create a response array
    $response = [
        'success' => true,
        'connected_devices' => $totalDevices
    ];

    // Return the response as JSON
    echo json_encode($response);
    exit;
}

// Function to parse Bluetooth output
function parseBluetoothOutput($output) {
    $controllers = [];
    $current_controller = null;

    foreach ($output as $line) {
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

        if (preg_match('/Device ([\w:]+) (.+)/', $line, $matches)) {
            if ($current_controller) {
                $current_controller['devices'][] = [
                    'mac' => $matches[1],
                    'name' => $matches[2]
                ];
            }
        }
    }

    if ($current_controller) {
        addController($controllers, $current_controller);
    }

    return $controllers;
}

function addController(&$controllers, $controller) {
    foreach ($controllers as &$existing) {
        if ($existing['mac'] === $controller['mac']) {
            $existing['devices'] = array_merge($existing['devices'], $controller['devices']);
            return;
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

// Removed the updateConfigFile function and its call
?>
