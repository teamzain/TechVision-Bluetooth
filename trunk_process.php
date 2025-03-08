<?php
session_start(); // Ensure session is started

// Generate unique file paths based on session ID
$session_id = session_id();
$ip_file = __DIR__ . '/ip_addresses/' . $session_id . '_ip_address.txt';
$db_file = __DIR__ . '/ip_addresses/' . $session_id . '_db_output.txt';

// Check if the IP address file exists
if (!file_exists($ip_file)) {
    die('IP address file not found. Please enter the IP address first.');
}

$ip_address = trim(file_get_contents($ip_file));

if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
    die('Invalid IP address in file');
}

// Check if the database configuration file exists
if (!file_exists($db_file)) {
    die('Database configuration file not found.');
}


// Read and parse the database configuration file
$db_config = [];
$db_lines = file($db_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($db_lines as $line) {
    if (preg_match('/^\$(\w+)\["(\w+)"\] = "(.*)";$/', $line, $matches)) {
        $db_config[$matches[2]] = $matches[3];
    }
}

// Extract database credentials
$db_user = $db_config['AMPDBUSER'] ?? '';
$db_pass = $db_config['AMPDBPASS'] ?? '';
$db_host = $db_config['AMPDBHOST'] ?? '';
$db_name = $db_config['AMPDBNAME'] ?? '';

// URL of the FreePBX server's execute.php script
$url = 'http://' . $ip_address . '/execute.php';

// Function to execute a command via the execute.php script
function executeCommand($url, $command) {
    $postData = json_encode(['command' => $command]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $postData,
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        return ['error' => 'Failed to execute command'];
    }

    return json_decode($result, true);
}

// Handle server-side form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle trunk deletion
    if (isset($_POST['deleteTrunk'])) {
        $trunkNameToDelete = $_POST['deleteTrunk'];
        $command = "mysql -u $db_user -p'$db_pass' -h $db_host -e \"DELETE FROM asteriskcdrdb.trunk_balance WHERE trunk_name = '$trunkNameToDelete'\"";
        $response = executeCommand($url, $command);
        if (isset($response['error'])) {
            echo $response['error'];
        } else {
            header("Location: trunk_management.php");
            exit;
        }
    } 
    // Handle trunk reset
    elseif (isset($_POST['resetTrunk'])) {
        $trunkNameToReset = $_POST['resetTrunk'];
        $command = "mysql -u $db_user -p'$db_pass' -h $db_host -e \"UPDATE asteriskcdrdb.trunk_balance SET remaining_trunk_minutes = total_trunk_minutes, remaining_calls = total_calls WHERE trunk_name = '$trunkNameToReset'\"";
        $response = executeCommand($url, $command);
        if (isset($response['error'])) {
            echo $response['error'];
        } else {
            header("Location: trunk_management.php");
            exit;
        }
    } 
    // Handle trunk update or insert
    else {
        $trunkName = $_POST['selected_trunk'];
        $totalTrunkMinutes = floatval($_POST['totalTrunkMinutes']);
        $totalCalls = intval($_POST['totalCalls']);
        $planExpiryDate = $_POST['planexpirydate'];
        $planExpiryDateFormatted = date('Y-m-d', strtotime($planExpiryDate));

        if ($trunkName === 'all') {
            $trunkList = trunkbalance_listtrunk();
            foreach ($trunkList as $trunk) {
                $currentTrunkName = $trunk['name'];
                $command = "mysql -u $db_user -p'$db_pass' -h $db_host -e \"INSERT INTO asteriskcdrdb.trunk_balance (trunk_name, total_trunk_minutes, total_calls, remaining_trunk_minutes, remaining_calls, plan_expiry_date) VALUES ('$currentTrunkName', $totalTrunkMinutes, $totalCalls, $totalTrunkMinutes, $totalCalls, '$planExpiryDateFormatted') ON DUPLICATE KEY UPDATE total_trunk_minutes = $totalTrunkMinutes, total_calls = $totalCalls, remaining_trunk_minutes = $totalTrunkMinutes, remaining_calls = $totalCalls, plan_expiry_date = '$planExpiryDateFormatted'\"";
                $response = executeCommand($url, $command);
                if (isset($response['error'])) {
                    echo $response['error'];
                    exit;
                }
            }
        } else {
            $command = "mysql -u $db_user -p'$db_pass' -h $db_host -e \"INSERT INTO asteriskcdrdb.trunk_balance (trunk_name, total_trunk_minutes, total_calls, remaining_trunk_minutes, remaining_calls, plan_expiry_date) VALUES ('$trunkName', $totalTrunkMinutes, $totalCalls, $totalTrunkMinutes, $totalCalls, '$planExpiryDateFormatted') ON DUPLICATE KEY UPDATE total_trunk_minutes = $totalTrunkMinutes, total_calls = $totalCalls, remaining_trunk_minutes = $totalTrunkMinutes, remaining_calls = $totalCalls, plan_expiry_date = '$planExpiryDateFormatted'\"";
            $response = executeCommand($url, $command);
            if (isset($response['error'])) {
                echo $response['error'];
            } else {
                header("Location: trunk_management.php");
                exit;
            }
        }
    }
}

// Function to list trunks from the asterisk database
function trunkbalance_listtrunk() {
    global $url, $db_user, $db_pass, $db_host;

    $db_query_command = "mysql -u $db_user -p'$db_pass' -h $db_host -e 'USE asterisk; SELECT name FROM trunks;'";
    $response = executeCommand($url, $db_query_command);
    
    $trunks = [];

    if (isset($response['output'])) {
        $lines = explode("\n", $response['output']);
        array_shift($lines); // Skip the headers
        foreach ($lines as $line) {
            if (!empty($line)) {
                $trunks[] = ['name' => $line];
            }
        }
    }
    return $trunks;
}
?>
