<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['server_ip']) && isset($data['connected_devices'])) {
    $server_ip = $data['server_ip'];
    $newCount = $data['connected_devices'];

    $file_path = 'config.txt';

    // Read existing data from the file
    $configData = [];
    if (file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
        $configData = json_decode($file_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $configData = [];
        }
    }

    // Find and update the specific entry
    $entryUpdated = false;
    foreach ($configData as &$entry) {
        if (isset($entry['server_ip']) && $entry['server_ip'] == $server_ip) {
            $entry['connected_devices'] = $newCount;
            $entryUpdated = true;
            break;
        }
    }

    if ($entryUpdated) {
        // Save updated data back to the file
        if (file_put_contents($file_path, json_encode($configData, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to write to config file.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Entry not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
}
?>
