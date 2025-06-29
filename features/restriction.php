<?php 
    $currentPage = basename($_SERVER["PHP_SELF"]);
    if ($currentPage != 'authentication.php') {
        if (!isset($_SESSION['usr_id']) && !isset($_SESSION['adm_id']) ) {
            header('Location: ../interfaces/authentication.php');
        exit();
    }
} 
?>