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

// Get the row ID from the query string
if (!isset($_GET['row_id'])) {
    die('Row ID not provided.');
}

$row_id = (int)$_GET['row_id']; // Convert row_id to integer

// URL of the FreePBX server's execute.php script
$url = 'http://' . $ip_address . '/execute.php';
$command = 'cat /etc/asterisk/sip_custom.conf';

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
$found_block = null;
$row_counter = 0;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    if ($line[0] === '[') {
        if (!empty($current_block)) {
            $config_blocks[] = $current_block;
            $row_counter++;
        }
        $current_block = ['name' => trim($line, '[]')];
    } else {
        list($key, $value) = explode('=', $line, 2);
        $current_block[trim($key)] = trim($value);
    }
}

// Add the last block if it exists
if (!empty($current_block)) {
    $config_blocks[] = $current_block;
}

// Ensure the row ID exists
if (!isset($config_blocks[$row_id])) {
    die('Invalid Row ID.');
}

$found_block = $config_blocks[$row_id];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update the block with the new values from the form
    $found_block['type'] = $_POST['type'];
    $found_block['username'] = $_POST['username'];
    $found_block['secret'] = $_POST['secret'];
    $found_block['host'] = $_POST['host'];
    $found_block['port'] = $_POST['port'];
    $found_block['context'] = $_POST['context'];
    $found_block['disallow'] = $_POST['disallow'];
    $found_block['ulaw'] = $_POST['allow_ulaw'];
    $found_block['alaw'] = $_POST['allow_alaw'];
    $found_block['nat'] = $_POST['nat'];
    $found_block['qualify'] = $_POST['qualify'];
    $found_block['canreinvite'] = $_POST['canreinvite'];
    $found_block['transport'] = $_POST['transport'];

    // Update the specific block in the config_blocks array
    $config_blocks[$row_id] = $found_block;

    // Generate the updated config file content
    $new_content = '';
    foreach ($config_blocks as $block) {
        $new_content .= '[' . $block['name'] . "]\n";
        foreach ($block as $key => $value) {
            if ($key !== 'name') {
                $new_content .= $key . '=' . $value . "\n";
            }
        }
        $new_content .= "\n";
    }

    // Save the updated content back to the configuration file
    $save_command = 'echo "' . addslashes($new_content) . '" > /etc/asterisk/sip_custom.conf';
    $save_data = json_encode(['command' => $save_command]);

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $save_data,
        ],
    ];

    $context = stream_context_create($options);
    $save_output = file_get_contents($url, false, $context);

    if ($save_output === FALSE) {
        die('Error occurred while saving the file contents');
    }

    // Redirect after saving
    header("Location: display.php?status=success");
    exit;
}

?>

<h2>Edit SIP Configuration Block</h2>
<form method="POST">
    <label>Type: <input type="text" name="type" value="<?php echo htmlspecialchars($found_block['type'] ?? ''); ?>"></label><br>
    <label>Username: <input type="text" name="username" value="<?php echo htmlspecialchars($found_block['username'] ?? ''); ?>"></label><br>
    <label>Secret: <input type="text" name="secret" value="<?php echo htmlspecialchars($found_block['secret'] ?? ''); ?>"></label><br>
    <label>Host: <input type="text" name="host" value="<?php echo htmlspecialchars($found_block['host'] ?? ''); ?>"></label><br>
    <label>Port: <input type="text" name="port" value="<?php echo htmlspecialchars($found_block['port'] ?? ''); ?>"></label><br>
    <label>Context: <input type="text" name="context" value="<?php echo htmlspecialchars($found_block['context'] ?? ''); ?>"></label><br>
    <label>Disallow: <input type="text" name="disallow" value="<?php echo htmlspecialchars($found_block['disallow'] ?? ''); ?>"></label><br>
    <label>Allow (ulaw): <input type="text" name="allow_ulaw" value="<?php echo htmlspecialchars($found_block['ulaw'] ?? ''); ?>"></label><br>
    <label>Allow (alaw): <input type="text" name="allow_alaw" value="<?php echo htmlspecialchars($found_block['alaw'] ?? ''); ?>"></label><br>
    <label>NAT: <input type="text" name="nat" value="<?php echo htmlspecialchars($found_block['nat'] ?? ''); ?>"></label><br>
    <label>Qualify: <input type="text" name="qualify" value="<?php echo htmlspecialchars($found_block['qualify'] ?? ''); ?>"></label><br>
    <label>Canreinvite: <input type="text" name="canreinvite" value="<?php echo htmlspecialchars($found_block['canreinvite'] ?? ''); ?>"></label><br>
    <label>Transport: <input type="text" name="transport" value="<?php echo htmlspecialchars($found_block['transport'] ?? ''); ?>"></label><br>
    <input type="submit" value="Save">
</form>
