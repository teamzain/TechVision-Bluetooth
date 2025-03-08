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

    // Parse the output to gather unique controller and device information
    $controllers = parseBluetoothOutput(explode("\n", $output));
    $totalDevices = array_sum(array_map(function($controller) {
        return count($controller['devices']);
    }, $controllers));

    // Update the configuration file if there's a change
    if (updateConfigFile($ip_address, $totalDevices)) {
        $response = [
            'success' => true,
            'connected_devices' => $totalDevices,
            'message' => 'Configuration updated'
        ];
    } else {
        $response = [
            'success' => true,
            'connected_devices' => $totalDevices,
            'message' => 'No changes in configuration'
        ];
    }

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

    return $response['output'];
}

function updateConfigFile($server_ip, $new_connected_devices) {
    $file_path = 'config.txt';

    // Read existing data from the file
    $data = [];
    $configChanged = false;

    if (file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
        $data = json_decode($file_content, true);

        // Ensure $data is an array
        if (!is_array($data)) {
            $data = [];
        }
    }

    // Check if the server IP already exists
    $found = false;
    foreach ($data as &$entry) {
        if (is_array($entry) && isset($entry['server_ip']) && $entry['server_ip'] == $server_ip) {
            // Update existing entry if there's a change
            if ($entry['connected_devices'] != $new_connected_devices) {
                $entry['connected_devices'] = $new_connected_devices;
                $configChanged = true;
            }
            $found = true;
            break;
        }
    }

    // If not found, add a new entry
    if (!$found) {
        $data[] = [
            'server_ip' => $server_ip,
            'connected_devices' => $new_connected_devices
        ];
        $configChanged = true;
    }

    if ($configChanged) {
        // Save updated data back to the file
        file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    }

    return $configChanged;
}
?>
