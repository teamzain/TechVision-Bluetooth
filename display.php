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
$row_id = 0; // Initialize row ID

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    if ($line[0] === '[') {
        if (!empty($current_block)) {
            $config_blocks[] = $current_block;
        }
        $current_block = ['name' => trim($line, '[]')];
        $current_block['row_id'] = $row_id++; // Assign a unique row ID
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

// Build the table
$table_html = '<table border="1" cellpadding="5" cellspacing="0">';
$table_html .= '<thead><tr><th>Block Name</th><th>Type</th><th>Username</th><th>Secret</th><th>Host</th><th>Port</th><th>Context</th><th>Disallow</th><th>Allow (ulaw)</th><th>Allow (alaw)</th><th>NAT</th><th>Qualify</th><th>Canreinvite</th><th>Transport</th><th>Action</th></tr></thead>';
$table_html .= '<tbody>';

foreach ($config_blocks as $block) {
    // Skip blocks without real data
    if (empty($block['username'])) {
        continue;
    }

    $table_html .= '<tr>';
    $table_html .= '<td>' . htmlspecialchars($block['name'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['type'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['username'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['secret'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['host'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['port'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['context'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['disallow'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['ulaw'] ?? '') . '</td>'; // Display ulaw
    $table_html .= '<td>' . htmlspecialchars($block['alaw'] ?? '') . '</td>'; // Display alaw
    $table_html .= '<td>' . htmlspecialchars($block['nat'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['qualify'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['canreinvite'] ?? '') . '</td>';
    $table_html .= '<td>' . htmlspecialchars($block['transport'] ?? '') . '</td>';

    // Add Action column with Edit link
    $table_html .= '<td>';
    $table_html .= '<a href="edit.php?row_id=' . urlencode($block['row_id']) . '">Edit</a>';
    $table_html .= ' | <a href="delete.php?username=' . urlencode($block['username']) . '"><img src="delete-icon.png" alt="Delete"></a>';
    $table_html .= '</td>';

    $table_html .= '</tr>';
}

$table_html .= '</tbody></table>';

echo '<h2>SIP Configuration</h2>';
echo $table_html;
?>
