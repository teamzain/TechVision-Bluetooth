<?php
// FreePBX server: save_chan_mobile2.php

$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo 'Invalid JSON data';
    exit;
}

if (!isset($data['fileContent'])) {
    http_response_code(400);
    echo 'No file content provided';
    exit;
}

$filePath = '/etc/asterisk/chan_mobile2.conf';

if (file_put_contents($filePath, $data['fileContent']) === false) {
    http_response_code(500);
    echo 'Error writing to file';
    exit;
}

echo 'File saved successfully';
?>
