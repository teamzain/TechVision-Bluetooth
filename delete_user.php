<?php
include 'config.php';
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_name'])){
    header('location:login_form.php');
}

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Delete user
    $delete_query = "DELETE FROM user_form WHERE id = $id";
    mysqli_query($conn, $delete_query);
    
    header('location:admin_page.php');
}
?>
