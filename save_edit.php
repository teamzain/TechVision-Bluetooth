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

// Retrieve form data with default values
$type = $_POST['type'] ?? '';
$username = $_POST['username'] ?? '';
$secret = $_POST['secret'] ?? '';
$host = $_POST['host'] ?? '';
$port = $_POST['port'] ?? '';
$context = $_POST['context'] ?? '';
$disallow = $_POST['disallow'] ?? '';
$allow_ulaw = $_POST['allow_ulaw'] ?? '';
$allow_alaw = $_POST['allow_alaw'] ?? '';
$nat = $_POST['nat'] ?? '';
$qualify = $_POST['qualify'] ?? '';
$canreinvite = $_POST['canreinvite'] ?? '';
$transport = $_POST['transport'] ?? '';

// Ensure all required fields are provided
if (empty($username)) {
    die('Username is required.');
}

// Define the URL of the FreePBX server's execute.php script
$url = 'http://' . $ip_address . '/execute.php';
$command = 'cat /etc/asterisk/sip_custom.conf';

// Prepare the data to fetch the current configuration
$data = json_encode(['command' => $command]);

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];

$context = stream_context_create($options);
$output = @file_get_contents($url, false, $context);

if ($output === FALSE) {
    $error = error_get_last();
    die('Error occurred while fetching the file contents: ' . $error['message']);
}

$response = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die('Error decoding JSON response: ' . json_last_error_msg());
}

// Parse the file content into an associative array
$lines = explode("\n", trim($response['output']));
$config_blocks = [];
$current_block = [];

// Read and organize the configuration blocks
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    if ($line[0] === '[') {
        if (!empty($current_block)) {
            $config_blocks[] = $current_block;
        }
        $current_block = ['name' => trim($line, '[]')];
    } else {
        list($key, $value) = explode('=', $line, 2);
        $current_block[trim($key)] = trim($value);
    }
}

if (!empty($current_block)) {
    $config_blocks[] = $current_block;
}

// Update only the specific block
$updated = false;
foreach ($config_blocks as &$block) {
    if ($block['name'] === 'purevoip' && isset($block['username']) && $block['username'] === $username) {
        // Update only the fields that have been provided in the form
        $block['type'] = $type ?: $block['type'];
        $block['username'] = $username ?: $block['username'];
        $block['secret'] = $secret ?: $block['secret'];
        $block['host'] = $host ?: $block['host'];
        $block['port'] = $port ?: $block['port'];
        $block['context'] = $context ?: $block['context'];
        $block['disallow'] = $disallow ?: $block['disallow'];
        $block['allow'] = $allow_ulaw ?: ($allow_alaw ?: $block['allow']);
        $block['nat'] = $nat ?: $block['nat'];
        $block['qualify'] = $qualify ?: $block['qualify'];
        $block['canreinvite'] = $canreinvite ?: $block['canreinvite'];
        $block['transport'] = $transport ?: $block['transport'];

        $updated = true;
        break;
    }
}

if (!$updated) {
    // Add a new block if not found
    $config_blocks[] = [
        'name' => 'purevoip',
        'type' => $type,
        'username' => $username,
        'secret' => $secret,
        'host' => $host,
        'port' => $port,
        'context' => $context,
        'disallow' => $disallow,
        'allow' => $allow_ulaw,
        'allow' => $allow_alaw,
        'nat' => $nat,
        'qualify' => $qualify,
        'canreinvite' => $canreinvite,
        'transport' => $transport
    ];
}

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
$save_output = @file_get_contents($url, false, $context);

if ($save_output === FALSE) {
    $error = error_get_last();
    die('Error occurred while saving the file contents: ' . $error['message']);
}

// Redirect to display.php with a success message
header("Location: display.php?status=success");
exit;
?>
