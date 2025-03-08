<?php
header('Content-Type: application/json');

// Read input from the request
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['server_ip'])) {
    $server_ip = $input['server_ip'];
    $file_path = 'config.txt';

    // Read existing data from the file
    if (file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
        $data = json_decode($file_content, true);

        // Ensure $data is an array
        if (!is_array($data)) {
            $data = [];
        }

        // Filter out the entry to be deleted
        $data = array_filter($data, function($entry) use ($server_ip) {
            return !(is_array($entry) && isset($entry['server_ip']) && $entry['server_ip'] == $server_ip);
        });

        // Save updated data back to the file
        if (file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error saving configuration.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Configuration file not found.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Server IP not provided.']);
}
?>
