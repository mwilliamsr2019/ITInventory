<?php
session_start();
require_once 'classes/User.php';

// Logout the user
User::logout();

// Redirect to login page
header('Location: login.php');
exit();
?>