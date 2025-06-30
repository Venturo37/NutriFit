<?php
    $server = '127.0.0.1'; 
    $user = 'root';
    $password = '';
    $database = 'nutrifit';

    $connection = mysqli_connect($server, $user, $password, $database);
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    } 

    // CHANGES
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // CHANGES
    return $connection;
    
    // $_SESSION['usr_id'] = 1; // For testing purposes, set a user ID
    // $_SESSION['adm_id'] = 1; // For testing purposes, set a user ID
    ?>