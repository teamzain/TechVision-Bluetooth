<?php include 'loader.html'; ?>

<?php
include 'config.php';
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_name'])){
    header('location:login_form.php');
}

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Fetch user details
    $query = "SELECT * FROM user_form WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
}

if(isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $id = $_POST['id'];

    $update_query = "UPDATE user_form SET name='$name', email='$email' WHERE id='$id'";
    mysqli_query($conn, $update_query);
    
    header('location:admin_page.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="form-container">
   
        <form action="" method="post">
        <h2>Edit User</h2>
            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
            <label for="name" >Name:</label>
            <input type="text" name="name" value="<?php echo $user['name']; ?>" required>
            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
            <input type="submit" name="update" value="Update"  class="form-btn">
        </form>
    </div>
</body>
</html>
