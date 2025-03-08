<?php
session_start();

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
$command = 'cat /etc/asterisk/chan_mobile.conf';

$data = json_encode(['command' => $command]);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];

$context  = stream_context_create($options);
$output = file_get_contents($url, false, $context);

if ($output === FALSE) {
    die('Error occurred while fetching the file contents');
}

$response = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die('Error decoding JSON response');
}

$lines = explode("\n", trim($response['output']));

// Parse the file content into an associative array
$config_blocks = [];
$current_block = [];
$row_id = 0;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    if ($line[0] === '[') {
        if (!empty($current_block)) {
            $config_blocks[] = $current_block;
        }
        $current_block = ['name' => trim($line, '[]')];
        $current_block['row_id'] = $row_id++;
    } else {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Assign values to the correct keys in the block
        $current_block[$key] = $value;
    }
}

if (!empty($current_block)) {
    $config_blocks[] = $current_block;
}

// Get the row_id from query parameters
$delete_row_id = isset($_GET['row_id']) ? intval($_GET['row_id']) : -1;

// Remove the block with the matching row_id
$config_blocks = array_filter($config_blocks, function($block) use ($delete_row_id) {
    return $block['row_id'] !== $delete_row_id;
});

// Build the updated content
$updated_content = '';
foreach ($config_blocks as $block) {
    $updated_content .= '[' . $block['name'] . "]\n";
    foreach ($block as $key => $value) {
        if ($key !== 'name' && $key !== 'row_id') {
            $updated_content .= $key . '=' . $value . "\n";
        }
    }
    $updated_content .= "\n";
}

// Save the updated content back to the file
$update_command = 'echo -e \'' . addslashes($updated_content) . '\' > /etc/asterisk/chan_mobile.conf';
$data = json_encode(['command' => $update_command]);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    die('Error occurred while updating the file');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechVision365</title>
    <script>
        function showPopupAndRedirect() {
            alert('Trunk deleted successfully.');
            window.location.href = 'display_trunk.php';
        }
        window.onload = showPopupAndRedirect;
    </script>
</head>
<body>
</body>
</html>
