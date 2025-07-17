<?php
// Name: Mr. CHUNG YHUNG YIE
// Project Name: connection.php
// Description: enabling output buffering with ob_start() to prevent accidental output from interfering with HTTP headers. It then establishes a connection
    //  to the local MySQL database named nutrifit using the root user account without a password. If the connection fails, the script stops and displays an error message. 
    // Next, it checks whether a PHP session has already been started; if not, it starts a new session to handle user state across pages. Finally, it returns the database 
    // connection so that any script including this file can use the established connection immediately.

// First Written: 1/6/2025
// Last Modified: 8/7/2025
    // ob_start();

    $server = '127.0.0.1'; 
    $user = 'root';
    $password = '';
    $database = 'nutrifit';

    $connection = mysqli_connect($server, $user, $password, $database);
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    } 

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return $connection;
?>