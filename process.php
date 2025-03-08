



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
$url = 'http://' . $ip_address . '/execute.php'; // Use the IP address from the file
$command = 'asterisk -rx "mobile show devices"';

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
    die('Error occurred');
}

// Decode the JSON response
$response = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die('Error decoding JSON response');
}

// Explode the output by line
$lines = explode("\n", trim($response['output'])); // Adjust based on the response structure

// Start building the table
$table_html = '<table class="content-table">';
$header_columns = preg_split('/\s+/', $lines[0]);
$table_html .= '<thead><tr>';
foreach ($header_columns as $header) {
    $table_html .= '<th>' . htmlspecialchars(trim($header)) . '</th>';
}
$table_html .= '</tr></thead>';
$table_html .= '<tbody>';

for ($i = 1; $i < count($lines); $i++) {
    if (trim($lines[$i]) === '') continue;
    $row_columns = preg_split('/\s+/', $lines[$i]);
    $table_html .= '<tr>';
    foreach ($row_columns as $column) {
        $table_html .= '<td>' . htmlspecialchars(trim($column)) . '</td>';
    }
    $table_html .= '</tr>';
}
$table_html .= '</tbody></table>';

echo $table_html;
?>
