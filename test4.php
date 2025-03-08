<?php
session_start();
$message = '';

// Function to execute the command
function executeCommand($command) {
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

    // Prepend the command with 'asterisk -rx'
    $command = 'asterisk -rx "' . $command . '"';

    // URL of the FreePBX server's execute.php script
    $url = 'http://' . $ip_address . '/execute.php';

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

    return $response;
}

// Handle POST requests for toggle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $command = $_POST['command'];
    $response = executeCommand($command);
    echo json_encode($response);
    exit; // Stop further script execution
}

// On page load, execute the unload command by default
$initialResponse = executeCommand('module unload chan_mobile.so');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toggle Button with Command Execution</title>
    <style>
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
    <div class="toggle-button" id="toggleBtn">
        <div class="toggle-circle"></div>
    </div>

    <pre id="output"><?php echo json_encode($initialResponse, JSON_PRETTY_PRINT); ?></pre> <!-- Display the output on page load -->

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
            fetch('', {  // The current script will handle the POST request
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
</body>
</html>
