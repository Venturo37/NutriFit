<!-- 
Name: Mr. Chung Yhung Yie
Project Name: connection.php
Description: Connects to a local MySQL database using the mysqli extension with the root user and no password, checks for connection error,
    sets mysqli to strict mode , starts a session if one doesn't exist and returns the database connection.

First Written: 1/6/2025
Last Modified: 8/7/2025
-->

<?php
    $server = '127.0.0.1'; 
    $user = 'root';
    $password = '';
    $database = 'nutrifit';

    $connection = mysqli_connect($server, $user, $password, $database);
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    } 

    $connection->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return $connection;
?>