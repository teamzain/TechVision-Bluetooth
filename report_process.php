<?php
session_start(); // Ensure session is started

// Generate unique file paths based on session ID
$session_id = session_id();
$ip_file = __DIR__ . '/ip_addresses/' . $session_id . '_ip_address.txt';
$db_file = __DIR__ . '/ip_addresses/' . $session_id . '_db_output.txt';

// Check if the IP address file exists
if (!file_exists($ip_file)) {
    die('IP address file not found. Please enter the IP address first.');
}

$ip_address = trim(file_get_contents($ip_file));

if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
    die('Invalid IP address in file');
}

// Check if the database configuration file exists
if (!file_exists($db_file)) {
    die('Database configuration file not found.');
}

// Read and parse the database configuration file
$db_config = [];
$db_lines = file($db_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($db_lines as $line) {
    if (preg_match('/^\$(\w+)\["(\w+)"\] = "(.*)";$/', $line, $matches)) {
        $db_config[$matches[2]] = $matches[3];
    }
}

// Extract database credentials
$db_user = $db_config['AMPDBUSER'] ?? '';
$db_pass = $db_config['AMPDBPASS'] ?? '';
$db_host = $db_config['AMPDBHOST'] ?? '';
$db_name = $db_config['AMPDBNAME'] ?? '';

// URL of the FreePBX server's execute.php script
$execute_url = 'http://' . $ip_address . '/execute.php';

// Function to send a command to execute.php
function sendCommand($command) {
    global $execute_url;
    
    $postData = json_encode(['command' => $command]);

    $ch = curl_init($execute_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        die("Curl error: " . curl_error($ch));
    }
    
    curl_close($ch);

    if ($http_code != 200) {
        die("HTTP error: " . $http_code . " Response: " . htmlspecialchars($response));
    }
    
    return json_decode($response, true);
}

// Function to convert seconds to MM:SS format
function formatSecondsToMMSS($seconds) {
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $seconds);
}

// Get user input from form
$selectedTrunk = $_POST['selected_trunk'] ?? '';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';

// SQL query to fetch hourly call distribution data
$hourly_sql = "SELECT DATE(calldate) as call_date, HOUR(calldate) as hour, COUNT(*) as calls 
    FROM cdr 
    WHERE (channel LIKE '%$selectedTrunk%' OR dstchannel LIKE '%$selectedTrunk%') 
    AND DATE(calldate) BETWEEN '$startDate' AND '$endDate' 
    GROUP BY call_date, hour";

// SQL query to fetch detailed CDR data
$db_query_command = "mysql -u $db_user -p'$db_pass' -h $db_host -e \"SELECT * FROM cdr WHERE (channel LIKE '%$selectedTrunk%' OR dstchannel LIKE '%$selectedTrunk%') AND DATE(calldate) BETWEEN '$startDate' AND '$endDate'\" asteriskcdrdb";

// Execute the commands
$hourly_response = sendCommand("mysql -u $db_user -p'$db_pass' -h $db_host -e \"$hourly_sql\" asteriskcdrdb");
$response = sendCommand($db_query_command);

if (isset($response['error'])) {
    die("Error executing command: " . htmlspecialchars($response['error']));
}

// Process hourly response data
$hourlyData = [];
if (isset($hourly_response['output'])) {
    $lines = explode("\n", $hourly_response['output']);
    
    // Skip the first line if it's a header row
    array_shift($lines);

    foreach ($lines as $line) {
        $fields = explode("\t", $line);
        if (count($fields) >= 3) {
            $call_date = $fields[0];
            $hour = (int)$fields[1];
            $calls = (int)$fields[2];

            $hourlyData[] = [
                'call_date' => $call_date,
                'hour' => $hour,
                'calls' => $calls
            ];
        }
    }
}

// Initialize variables
$totalCalls = 0;
$incomingCalls = 0;
$outgoingCalls = 0;
$completedCalls = 0;
$missedCalls = 0;
$totalDuration = 0;
$totalRingTime = 0;
$cdrData = [];

// Process response data
if (isset($response['output'])) {
    $lines = explode("\n", $response['output']);
    
    // Skip the first line if it's a header row
    array_shift($lines);

    foreach ($lines as $line) {
        $fields = explode("\t", $line);
        if (count($fields) >= 12) {
            $calldate = $fields[0];
            $clid = $fields[1];
            $src = $fields[2];
            $dst = $fields[3];
            $channel = $fields[5];
            $dstchannel = $fields[6];
            $duration = (int)$fields[9];
            $billsec = (int)$fields[10];
            $disposition = trim($fields[11]);

            $cdrData[] = [
                'calldate' => $calldate,
                'src' => $src,
                'dst' => $dst,
                'channel' => $channel,
                'dstchannel' => $dstchannel,
                'duration' => $duration,
                'billsec' => $billsec,
                'disposition' => $disposition
            ];
            
            $totalCalls++;
            
            if (strpos($channel, $selectedTrunk) !== false) {
                $outgoingCalls++;
            }
            if (strpos($dstchannel, $selectedTrunk) !== false) {
                $incomingCalls++;
            }
            
            if ($disposition == 'ANSWERED') {
                $completedCalls++;
            } elseif ($disposition == 'NO ANSWER' || $disposition == 'BUSY') {
                $missedCalls++;
            }

            $totalDuration += $billsec;
            $totalRingTime += $duration;
        }
    }
}

// Calculate percentages
$percentageMissed = ($totalCalls > 0) ? ($missedCalls / $totalCalls) * 100 : 0;
$percentageDuration = ($totalCalls > 0) ? ($totalDuration / $totalCalls) * 100 : 0;

// Calculate average duration and average ring time
$averageDuration = ($totalCalls > 0) ? ($totalDuration / $totalCalls) : 0;
$averageRingTime = ($totalCalls > 0) ? ($totalRingTime / $totalCalls) : 0;
$percentageMissedFormatted = number_format($percentageMissed, 2);
$percentageDurationFormatted = number_format($percentageDuration, 2);

// Debug output
error_log("Total Calls: $totalCalls");
error_log("Incoming Calls: $incomingCalls");
error_log("Outgoing Calls: $outgoingCalls");
error_log("Completed Calls: $completedCalls");
error_log("Missed Calls: $missedCalls");
error_log("Total Duration: $totalDuration");
error_log("Total Ring Time: $totalRingTime");
error_log("Average Duration: $averageDuration");
error_log("Average Ring Time: $averageRingTime");

?>


<!DOCTYPE html>
<html>
<head>
    <title>Trunk Report</title>
    <style>
        /* Navbar container */
        .navbar {
            overflow: hidden;
            background-color: #333;
        }

        /* Navbar links */
        .navbar a {
            float: left;
            display: block;
            color: #f2f2f2;
            text-align: center;
            padding: 14px 20px;
            text-decoration: none;
        }

        /* On hover, the links change color */
        .navbar a:hover {
            background-color: #ddd;
            color: black;
        }

        /* Sections to show/hide */
        .section {
            display: none;
        }

        /* Show home section by default */
        #home {
            display: block;
        }
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500&display=swap");

nav {
  background: #fff;
  border-radius: 9px;
  padding: 30px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);
}
nav ul li {
  display: inline-block;
  list-style: none;
  font-size: 2rem;
  padding: 0 10px;
  margin: 0 20px;
  cursor: pointer;
  position: relative;
  color: #333;
}
nav ul li:after {
  content: "";
  width: 0;
  height: 3px;
  background: #2192ff;
  position: absolute;
  left: 0;
  bottom: -10px;
  transition: 0.5s;
}
nav ul li:hover::after {
  width: 100%;
}
table {
            width: 99%;
            border-collapse: collapse;
            margin: 20px 0;
            font-family: Arial, sans-serif;
        }
        caption {
            font-size: 1.5em;
            margin-bottom: 10px;
            text-align: left;
            font-weight: bold;
        }
        thead th, tbody td {
            padding: 8px;
            text-align: left;
        }
        tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tbody tr:hover {
            background-color: #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        td {
            border: 1px solid #ddd;
        }
        .table-container {
            display: flex;
            justify-content: space-between;
        }
        .table-container table {
            width: 48%;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->

      <nav>
        <ul>
          <li href="#" onclick="showSection('home')">Home</li>
          <li href="#" onclick="showSection('call_statistics')">Call Stats</li>
          <li href="#" onclick="showSection('hourly_statistics')">Hourly Reports</li>
         
        </ul>
      </nav>
    
    <!-- <div class="navbar">
        <a href="#" onclick="showSection('home')">Home</a>
        <a href="#" onclick="showSection('call_statistics')">Call Statistics</a>
        <a href="#" onclick="showSection('hourly_statistics')">Hourly Statistics</a>
    </div> -->

    <!-- Home Section -->
    <div id="home" class="section">
        <h2>Trunk Report</h2>
        <table width='99%' cellpadding=3 cellspacing=3 border=0>
            <thead>
                <tr>
                    <td valign=top width='50%'>
                        <table width='100%' border=0 cellpadding=0 cellspacing=0>
                            <caption>Report Data</caption>
                            <tbody>
                                <tr>
                                    <td>Start Date:</td>
                                    <td><?php echo htmlspecialchars($startDate); ?></td>
                                </tr>
                                <tr>
                                    <td>End Date:</td>
                                    <td><?php echo htmlspecialchars($endDate); ?></td>
                                </tr>
                                <tr>
                                    <td>Selected Trunk:</td>
                                    <td><?php echo htmlspecialchars($selectedTrunk); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td valign=top width='50%'>
                        <table width='100%' border=0 cellpadding=0 cellspacing=0>
                            <caption>Results</caption>
                            <tbody>
                                <tr>
                                    <td>Number Of Calls:</td>
                                    <td><?php echo $totalCalls; ?></td>
                                </tr>
                                <tr>
                                    <td>Total Time:</td>
                                    <td><?php echo formatSecondsToMMSS($totalRingTime); ?></td>
                                </tr>
                                <tr>
                                    <td>Total Talk Time:</td>
                                    <td><?php echo formatSecondsToMMSS($totalDuration); ?></td>
                                </tr>
                                <tr>
                                    <td>Average Ring Time:</td>
                                    <td><?php echo formatSecondsToMMSS($averageRingTime); ?></td>
                                </tr>
                                <tr>
                                    <td>Average Talk Time/Duration:</td>
                                    <td><?php echo formatSecondsToMMSS($averageDuration); ?></td>
                                </tr>
                                <tr>
                                    <td>Total Answered Calls:</td>
                                    <td><?php echo $completedCalls; ?></td>
                                </tr>
                                <tr>
                                    <td>Total Missed Calls:</td>
                                    <td><?php echo $missedCalls; ?></td>
                                </tr>
                                <tr>
                                    <td>Missed Calls Percentage:</td>
                                    <td><?php echo $percentageMissedFormatted; ?>%</td>
                                </tr>
                                <tr>
                                    <!-- <td>Duration Percentage:</td>
                                    <td><?php echo $percentageDurationFormatted; ?>%</td> -->
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </thead>
        </table>
         <!-- CanvasJS Line Chart -->
    

    </div>
    <br><br>
    <script src='https://canvasjs.com/assets/script/canvasjs.min.js'></script>
    <script>
        window.onload = function () {
            var chartCDR = new CanvasJS.Chart('chartContainerCDR', {
                animationEnabled: true,
                theme: 'light2',
                title: {  
                    text: 'Call Duration Analysis'
                },
                axisY: {
                    title: 'Duration (minutes)',
                    includeZero: true
                },
                data: [{
                    type: 'line', // Change the chart type to 'line'
                    showInLegend: true,
                    legendMarkerColor: 'grey',
                    legendText: 'Duration',
                    dataPoints: [
                        <?php
                        foreach ($cdrData as $entry) {
                            echo "{ y: " . ($entry['duration'] / 60) . ", label: '" . htmlspecialchars($entry['calldate']) . "' },";
                        }
                        ?>
                    ]
                }]
            });
            chartCDR.render();
        }
    </script>

    <div id='chartContainerCDR' style='height: 300px; width: 100%;'></div>

    <!-- Call Statistics Section -->
    <div id="call_statistics" class="section">
    
        <div class="container py-5">
            <header class="text-center text-white">
                <h1 class="display-4">Call Statistics</h1>
            </header>
            <table style='border-collapse: collapse; margin: 25px 0; margin-left: 1%; font-size: 0.9em; min-width: 400px; border-radius: 5px 5px 0 0; overflow: hidden; box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);'>
                <thead>
                    <tr style='background-color: #009879; color: #ffffff; text-align: left; font-weight: bold;'>
                        <th style='padding: 12px 15px;'>Trunk</th>
                        <th style='padding: 12px 15px;'>Total</th>
                        <th style='padding: 12px 15px;'>Incoming</th>
                        <th style='padding: 12px 15px;'>Outgoing</th>
                        <th style='padding: 12px 15px;'>Completed</th>
                        <th style='padding: 12px 15px;'>Missed</th>
                        <th style='padding: 12px 15px;'>% Missed</th>
                        <th style='padding: 12px 15px;'>Duration</th>
                        <!-- <th style='padding: 12px 15px;'>% Duration</th> -->
                        <th style='padding: 12px 15px;'>Avg Duration</th>
                        <th style='padding: 12px 15px;'>Total Ring Time</th>
                        <th style='padding: 12px 15px;'>Avg Ring Time</th>
                        <th style='padding: 12px 15px;'>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style='padding: 12px 15px;'><?php echo htmlspecialchars($selectedTrunk); ?></td>
                        <td style='padding: 12px 15px;'><?php echo $totalCalls; ?></td>
                        <td style='padding: 12px 15px;'><?php echo $incomingCalls; ?></td>
                        <td style='padding: 12px 15px;'><?php echo $outgoingCalls; ?></td>
                        <td style='padding: 12px 15px;'><?php echo $completedCalls; ?></td>
                        <td style='padding: 12px 15px;'><?php echo $missedCalls; ?></td>
                        <td style='padding: 12px 15px;'><?php echo $percentageMissedFormatted; ?>%</td>
                        <td style='padding: 12px 15px;'><?php echo formatSecondsToMMSS($totalDuration); ?></td>
                        <!-- <td style='padding: 12px 15px;'><?php echo $percentageDurationFormatted; ?>%</td> -->
                        <td style='padding: 12px 15px;'><?php echo formatSecondsToMMSS($averageDuration); ?></td>
                        <td style='padding: 12px 15px;'><?php echo formatSecondsToMMSS($totalRingTime); ?></td>
                        <td style='padding: 12px 15px;'><?php echo formatSecondsToMMSS($averageRingTime); ?></td>
                        <td style='padding: 12px 15px;'><?php echo htmlspecialchars($startDate); ?></td>
                    </tr>
                </tbody>
            </table>
            <canvas id="callChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Hourly Statistics Section (empty for now) -->
    <div id="hourly_statistics" class="section">
        <h2>Hourly Statistics</h2>
        <!-- Content for hourly statistics goes here -->

        <?php if (!empty($hourlyData)): ?>
            <h2 style='font-size: 26px; margin: 20px 0; text-align: center;'>Hourly Statistics</h2>
            <div style='margin-top: 1.3rem;'>
                <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                    <thead>
                        <tr style='background-color: #009879; color: #ffffff; text-align: left; font-weight: bold;'>
                            <th style='padding: 12px 15px;'>Date</th>
                            <th style='padding: 12px 15px;'>Hour Range</th>
                            <th style='padding: 12px 15px;'>Number Of Calls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hourlyData as $entry): ?>
                            <?php $hourRange = sprintf('%02d:00 - %02d:59', $entry['hour'], ($entry['hour'] + 1) % 24); ?>
                            <tr>
                                <td style='padding: 12px 15px;'><?php echo htmlspecialchars($entry['call_date']); ?></td>
                                <td style='padding: 12px 15px;'><?php echo $hourRange; ?></td>
                                <td style='padding: 12px 15px;'><?php echo $entry['calls']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No hourly data found for the selected criteria.</p>
        <?php endif; ?>
    </div>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            var sections = document.querySelectorAll('.section');
            sections.forEach(function(section) {
                section.style.display = 'none';
            });

            // Show the selected section
            document.getElementById(sectionId).style.display = 'block';
        }
   
</script>
</body>
</html>




