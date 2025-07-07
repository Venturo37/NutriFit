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