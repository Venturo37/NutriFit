<?php
    // if (session_start() === PHP_SESSION_NONE) {
    //     session_start();
    // }
    $current_page = basename($_SERVER['PHP_SELF'], ".php");

    $header_class = 'header_transparent';

    // if ($current_page === 'fitness_page' || $current_page === "fitness_session" || $current_page === "fitness_result") {
    if (in_array($current_page, ['fitness_page', 'fitness_page', 'fitness_page', 'about_us'])) {
        $header_class = 'header_red';
    } elseif ($current_page === 'diet_page_name') {
        $header_class = 'header_green';
    // } elseif ($current_page === 'user_acc_page_name' || (isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'admin')) {
    } else {
        $header_class = 'header_transparent';
    } 
        
    if (!isset($_SESSION['usr_id']) && !isset($_SESSION['adm_id'])) {
        $logo_link = 'authentication.php';
    } elseif (isset($_SESSION['usr_id'])) {
        $logo_link = '';
    } elseif (isset($_SESSION['adm_id'])) {
        $logo_link = 'admin_dashboard.php';
    } 

    include('../features/embed.php'); 
?>

<header class="<?php echo $header_class ?>">
    <div class="logo">
        <a href="<?php echo $logo_link ?>">
            <h1>NutriFit</h1>
            <p>By The Enthusiasts</p>
        </a>
    </div>
    <nav>
        <ul>
            <?php if (!isset($_SESSION['usr_id']) && !isset($_SESSION['adm_id'])) { ?>
                
            <?php } elseif (isset($_SESSION['usr_id'])) {?>
                <li><a href="#">Fitness</a></li>
                <li><a href="#">Diet</a></li>
                <li><a href="../interfaces/about_us.php">About Us</a></li>
                <li><a href="../interfaces/user_profile.php">Profile</a></li>
                <li><a href="../interfaces/authentication.php">Log Out</a></li>
            <?php } elseif (isset($_SESSION['adm_id'])) {?>
                <li><a href="../interfaces/admin_user_management.php">Users</a></li>
                <li><a href="#">Fitness</a></li>
                <li><a href="#">Diet Us</a></li>
                <li><a href="#">Feedback</a></li>
                <li><a href="../interfaces/authentication.php">Log Out</a></li>
            <?php } ?>
        </ul>
    </nav>
</header>
