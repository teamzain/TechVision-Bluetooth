<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $found_block['name'] = $_POST['name'];
    $found_block['type'] = $_POST['type'];
    $found_block['context'] = $_POST['context'];
    $found_block['dialstring'] = $_POST['dialstring'];

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
    $save_command = 'echo "' . addslashes($new_content) . '" > /etc/asterisk/chan_mobile.conf';
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
    header("Location: display_trunk.php?status=success");
    exit;
}

?>

<h2>Edit Configuration Block</h2>
<form method="POST">
    <label>Trunk Name: <input type="text" name="name" value="<?php echo htmlspecialchars($found_block['name'] ?? ''); ?>"></label><br>
    <label>Type: <input type="text" name="type" value="<?php echo htmlspecialchars($found_block['type'] ?? ''); ?>"></label><br>
    <label>Context: <input type="text" name="context" value="<?php echo htmlspecialchars($found_block['context'] ?? ''); ?>"></label><br>
    <label>Dialstring: <input type="text" name="dialstring" value="<?php echo htmlspecialchars($found_block['dialstring'] ?? ''); ?>"></label><br>
    <input type="submit" value="Save">
</form>
