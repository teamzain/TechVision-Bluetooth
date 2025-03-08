<?php


function getFreePBXDBConfig() {
    $file = __DIR__ . '/ip_address.txt';

if (!file_exists($file)) {
    die('IP address file not found. Please enter the IP address first.');
}

$ip_address = trim(file_get_contents($file));

if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
    die('Invalid IP address in file');
}
    $url = 'http://' . $ip_address . '/execute.php'; // URL to the execute.php file
    $command = 'cat /etc/freepbx.conf'; // Command to get the config file contents

    $data = json_encode(['command' => $command]);
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data,
        ],
    ];
    $context  = stream_context_create($options);

    // Fetch the output from execute.php
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        throw new Exception('Error fetching config.');
    }

    // Decode the JSON response
    $data = json_decode($response, true);

    if (isset($data['error'])) {
        throw new Exception($data['error']);
    }

    // Process the output
    $config = [];
    $lines = explode("\n", trim($data['output']));
    foreach ($lines as $line) {
        if (preg_match('/^\$amp_conf\["(.*?)"\]\s*=\s*"(.*?)";/', $line, $matches)) {
            $key = $matches[1];
            $value = $matches[2];
            $config[$key] = $value;
        }
    }

    return $config;
}

try {
    $dbConfig = getFreePBXDBConfig();

    $dbUser = $dbConfig['AMPDBUSER'];
    $dbPass = $dbConfig['AMPDBPASS'];
    $dbHost = $dbConfig['AMPDBHOST'];
    $dbName = $dbConfig['AMPDBNAME'];
    $dbEngine = $dbConfig['AMPDBENGINE'];

    // Example usage for a MySQL connection
    if ($dbEngine === 'mysql') {
        $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo "Connected to the database successfully.";
    } else {
        throw new Exception("Unsupported database engine: $dbEngine");
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
