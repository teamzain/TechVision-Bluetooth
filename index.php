



<?php include 'loader.html'; ?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login Form</title>
   <link rel="stylesheet" href="css2/style.css">
</head>
<body>
   <div class="form-container">
      <form action="login_process.php" method="post">
         <h3>Login Now</h3>
         <?php
         if (isset($_GET['error'])) {
            echo '<span class="error-msg">' . htmlspecialchars($_GET['error']) . '</span>';
         }
         ?>
         <input type="text" name="name" required placeholder="Enter your username">
         <input type="password" name="password" required placeholder="Enter your password">
         <input type="submit" name="submit" value="Login Now" class="form-btn">
           <!-- <p>don't have an account? <a href="register_form.php">register now</a></p> -->
      </form>
   </div>
   <div id="loader" class="hidden"></div>
<script>
   function showLoader() {
    document.getElementById('loader').classList.remove('hidden');
}

function hideLoader() {
    document.getElementById('loader').classList.add('hidden');
}

function attemptLogin() {
    // Display the loader
    showLoader();

    // Simulate a successful login (replace this with your actual login logic)
    setTimeout(() => {
        // Hide the loader after a delay (replace this with your actual success logic)
        hideLoader();

        // Rest of your login logic
        // ...

    }, 2000);
}

</script>
</body>
</html>
