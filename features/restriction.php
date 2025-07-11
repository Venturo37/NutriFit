<?php 
// Name: Mr. CHUNG YHUNG YIE
// Project Name: restriction.php
// Description: checks if the current page is not authentication.php and, if no user or admin session is active, 
//     redirects the user to the authentication page to prevent unauthorized access.
// First Written: 1/7/2025
// Last Modified: 5/7/2025 
    $currentPage = basename($_SERVER["PHP_SELF"]);
    if ($currentPage != 'authentication.php') {
        if (!isset($_SESSION['usr_id']) && !isset($_SESSION['adm_id']) ) {
            header('Location: ../interfaces/authentication.php');
        exit();
    }
} 
?>