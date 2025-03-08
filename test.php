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

function runExpectCommand($url, $commands) {
    // Create the Expect script by joining the commands
    $expectScript = implode("\n", $commands);
    
    // Add the initial command to spawn bluetoothctl and ensure the agent is registered
    $expectScript = <<<EOL
spawn sudo /usr/bin/bluetoothctl
expect "#"
send -- "agent on\\r"
expect "Agent registered"
$expectScript

expect eof
EOL;

    // Prepare the command to run the Expect script
    $command = <<<EOL
/usr/bin/expect -c '$expectScript'
EOL;

    // Send the command via POST request to the provided URL
    $data = json_encode(['command' => $command]);

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data,
        ],
    ];

    $context  = stream_context_create($options);

    // Send the request and capture the output
    $output = @file_get_contents($url, false, $context);

    if ($output === FALSE) {
        $error = error_get_last();
        die('Error occurred while sending request: ' . $error['message']);
    }

    // Decode the JSON response
    $response = json_decode($output, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Error decoding JSON response');
    }

    if (!isset($response['output'])) {
        die('No output found in response');
    }

    // Debugging output - Write raw output to a file for inspection
    file_put_contents('raw_bluetooth_output.txt', $response['output'], FILE_APPEND);

    // Filter out unnecessary lines, like [CHG] lines
    $filteredOutput = preg_replace('/\[CHG\] Controller .* Pairable: .*/', '', $response['output']);

    return $filteredOutput;
}

// Define the commands to run after agent registration
$commands = [
    'send -- "devices\\r"',
    'expect "#"'
];

// Execute the command and get the output
$output = runExpectCommand($url, $commands);

// Display the filtered output
echo $output;
?>
