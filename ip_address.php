<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter IP Address</title>
    <link rel="icon" href="logo.png" type="image/png" style="height:50px"> <!-- Favicon -->
    <style>
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800,900');
        body {
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
            font-size: 15px;
            line-height: 1.7;
            color: #1f2029;
            background-color: #fff;
            background-image: url('https://assets.codepen.io/1462889/back-page.svg');
            background-position: center;
            background-repeat: no-repeat;
            background-size: 101%;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            text-align: center;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .input-field {
            padding: 10px;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .button {
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            font-size: 15px;
            line-height: 2;
            height: 50px;
            transition: all 200ms linear;
            border-radius: 4px;
            width: 100%;
            letter-spacing: 1px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            border: none;
            cursor: pointer;
            background-color: #102770;
            color: #ffeba7;
            box-shadow: 0 12px 35px 0 rgba(16, 39, 112, .25);
        }
        .button:hover {
            background-color: #ffeba7;
            color: #102770;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Enter Server IP</h1>
        <form action="save_ip.php" method="post">
            <div class="form-container">
                <label for="ip_address">IP Address:</label>
                <input type="text" id="ip_address" name="ip_address" class="input-field" required>
                <input type="submit" value="Submit" class="button">
            </div>
        </form>
    </div>
</body>
</html>
