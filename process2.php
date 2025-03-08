<?php
header('Content-Type: application/json');

$file = 'data.json';

// Read existing data from the file
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Update the data
$updated = false;
foreach ($data as &$row) {
    if ($row['serverIP'] === $input['serverIP']) {
        $row['connectedDevices'] = $input['connectedDevices'];
        $row['allowedDevices'] = $input['allowedDevices'];
        $updated = true;
        break;
    }
}

if (!$updated) {
    $data[] = $input;
}

// Save updated data to the file
file_put_contents($file, json_encode($data));

// Return the updated data
echo json_encode($data);
