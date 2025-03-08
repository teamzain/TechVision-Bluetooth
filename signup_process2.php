<?php

$ip_file = __DIR__ . '/ip_address.txt';
$db_file = __DIR__ . '/db.txt';

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
$db_user = 'root';
$db_pass = 'Aa-112233';
$db_host = 'localhost';
$db_name = 'techvision';

// URL of the FreePBX server's execute.php script
$url = 'http://' . 146.190.224.81 . '/execute.php';

// Function to execute a command via the execute.php script
function executeCommand($url, $command) {
    $postData = json_encode(['command' => $command]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $postData,
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        return ['error' => 'Failed to execute command'];
    }

    return json_decode($result, true);
}


if(isset($_POST['submit'])){

   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $pass = md5($_POST['password']);
   $cpass = md5($_POST['cpassword']);
   $user_type = $_POST['user_type'];
   $command = "mysql -u $db_user -p'$db_pass' -h $db_host -e \"SELECT * FROM user_form WHERE email = '$email' && password = '$pass' \"";
   $select = " ";

   $result = mysqli_query($conn, $select);

   if(mysqli_num_rows($result) > 0){

      $error[] = 'user already exist!';

   }else{

      if($pass != $cpass){
         $error[] = 'password not matched!';
      }else{
         $insert = "INSERT INTO user_form(name, email, password, user_type) VALUES('$name','$email','$pass','$user_type')";
         mysqli_query($conn, $insert);
         header('location:login_form.php');
      }
   }

};


?>