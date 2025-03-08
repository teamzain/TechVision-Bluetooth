<?php
// Get parameters from URL
$tech = isset($_GET['tech']) ? $_GET['tech'] : '';
$display = isset($_GET['display']) ? $_GET['display'] : 'trunks';
$extdisplay = isset($_GET['extdisplay']) ? $_GET['extdisplay'] : '';

// Define URLs
$loginUrl = "http://192.168.192.236/admin/config.php";
$targetUrl = $loginUrl . "?display=" . urlencode($display) . ($tech ? "&tech=" . urlencode($tech) : '') . ($extdisplay ? "&extdisplay=" . urlencode($extdisplay) : '');

// Initialize cURL for login
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username' => 'admin',
    'password' => 'Aa-112233'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, false);

// Execute login request
$loginResponse = curl_exec($ch);

// Check for login errors
if (curl_errno($ch)) {
    echo 'Login Error: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

// Fetch the target page after login
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");

$html = curl_exec($ch);

// Check for cURL errors
if ($html === false) {
    echo 'cURL Error: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Load the HTML into DOMDocument for manipulation
$dom = new DOMDocument;
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();

// Create an XPath object
$xpath = new DOMXPath($dom);

// Remove unwanted sections
$removeQueries = [
    '//div[@class="navbar-header"]',
    '//div[@class="collapse navbar-collapse" and @id="fpbx-menu-collapse"]',
    '//ul[@class="stuck-right"]',
    '//input[@id="search"]',
    '//div[contains(@class, "alert") and contains(text(), "Security Warning")]',
    '//div[contains(@class, "alert") and contains(text(), "1 extension/trunk has weak secret")]',
    '//div[contains(text(), "Module: \"Core\", File: \"/var/www/html/admin/modules/core/etc/extensions.conf altered\")]',
    '//div[@id="toolbar-all"]',
    '//div[@class="well well-info"]',
];

foreach ($removeQueries as $query) {
    $nodesToRemove = $xpath->query($query);
    if ($nodesToRemove && $nodesToRemove instanceof DOMNodeList) {
        foreach ($nodesToRemove as $node) {
            $node->parentNode->removeChild($node);
        }
    }
}

// Extract the relevant content based on 'display'
$relevantContent = $xpath->query('//div[@class="container-fluid"]');

$contentHtml = '';
if ($relevantContent && $relevantContent instanceof DOMNodeList && $relevantContent->length > 0) {
    foreach ($relevantContent as $node) {
        $contentHtml .= $dom->saveHTML($node);
    }
} else {
    $contentHtml = '<p>No data found</p>';
}

// Dropdown menu for adding trunks
$dropdownHtml = '
<div class="dropdown">
    <button class="dropdown-button" id="dropdownButton">Add Trunk</button>
    <div class="dropdown-content" id="dropdownContent">
        <a href="?display=trunks&amp;tech=PJSIP">Add SIP (chan_pjsip) Trunk</a>
        <a href="?display=trunks&amp;tech=DAHDI">Add DAHDi Trunk</a>
        <a href="?display=trunks&amp;tech=IAX2">Add IAX2 Trunk</a>
        <a href="?display=trunks&amp;tech=ENUM">Add ENUM Trunk</a>
        <a href="?display=trunks&amp;tech=DUNDI">Add DUNDi Trunk</a>
        <a href="?display=trunks&amp;tech=CUSTOM">Add Custom Trunk</a>
    </div>
</div>
';

// Action buttons for submit and reset
$actionButtonsHtml = '
<div id="action-buttons">
    <button id="action-bar-hide" class="btn"><i class="fa fa-angle-double-right"></i></button>
    <input name="submit" type="submit" value="Submit" id="submit">
    <input name="reset" type="submit" value="Reset" id="reset">
</div>
';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logic to handle form submission here
    // For example, perform the action based on the submitted data
}

// Output the page with custom styling
echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Trunk Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown-button.active + .dropdown-content {
            display: block;
        }

        #action-buttons {
            margin-top: 20px;
        }

        #action-buttons .btn, #action-buttons input[type="submit"] {
            margin-right: 10px;
        }

        #action-buttons input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
        }

        #action-buttons input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <form method="POST" action="">
        <div id="trunk-table">
            ' . $contentHtml . '
        </div>
        ' . $dropdownHtml . '
        ' . $actionButtonsHtml . '
    </form>
</div>
<script>
    document.getElementById("dropdownButton").addEventListener("click", function() {
        var dropdownContent = document.getElementById("dropdownContent");
        dropdownContent.style.display = dropdownContent.style.display === "block" ? "none" : "block";
    });
</script>
</body>
</html>
';
?>
