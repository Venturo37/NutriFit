<?php
// Name: Mr. CHUNG YHUNG YIE, Mr. CHAN RUI JIE
// Project Name: header.php
// Description: dynamically sets the header’s style and logo link based on the current page and user session 
//     (user, admin, or guest), then renders a responsive navigation menu and sidebar with different links for users and admins, 
//     and includes JavaScript to toggle the sidebar visibility on smaller screens.

// First Written: 1/6/2025
// Last Modified: 5/7/2025 

    $current_page = basename($_SERVER['PHP_SELF'], ".php");

    $header_class = 'header_transparent';

    // if ($current_page === 'fitness_page' || $current_page === "fitness_session" || $current_page === "fitness_result") {
    if (in_array($current_page, ['fitness_page', 'fitness_session', 'fitness_result', 'about_us'])) {
        $header_class = 'header_red';
    } elseif (in_array($current_page, ['diet_page_name', 'selected_meal'])) {
        $header_class = 'header_green';
    // } elseif ($current_page === 'user_acc_page_name' || (isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'admin')) {
    } else {
        $header_class = 'header_transparent';
    } 
        
    if (!isset($_SESSION['usr_id']) && !isset($_SESSION['adm_id'])) {
        $logo_link = '../interfaces/authentication.php';
    } elseif (isset($_SESSION['usr_id'])) {
        $logo_link = '../interfaces/fitness_page.php';
    } elseif (isset($_SESSION['adm_id'])) {
        $logo_link = '../interfaces/admin_dashboard.php';
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
                <li><a href="../interfaces/fitness_page.php">Fitness</a></li>
                <li><a href="../interfaces/diet_page_name.php">Diet</a></li>
                <li><a href="../interfaces/about_us.php">About Us</a></li>
                <li><a href="../interfaces/user_profile.php">Profile</a></li>
                <li><a href="../interfaces/authentication.php">Log Out</a></li>
            <?php } elseif (isset($_SESSION['adm_id'])) {?>
                <li><a href="../interfaces/admin_user_management.php">Users</a></li>
                <li><a href="../interfaces/adminfitnesstable.php">Fitness</a></li>
                <li><a href="../interfaces/adminmealtable.php">Diet</a></li>
                <li><a href="../interfaces/feedback.php">Feedback</a></li>
                <li><a href="../interfaces/authentication.php">Log Out</a></li>
            <?php } ?>
        </ul>
    </nav>
    <?php if (isset($_SESSION['usr_id']) ||isset($_SESSION['adm_id'])) { ?>
        <div class="hamburger" id="hamburger-btn">
            <i class="fa-solid fa-bars"></i>
        </div>   
    <?php } ?>             
</header>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="sidebar" id="sidebar">
        <div class="close-btn" id="close-btn">
            <i class="fa-solid fa-xmark"></i>
        </div>
        <ul>
            <?php if (!isset($_SESSION['usr_id']) && !isset($_SESSION['adm_id'])) { ?>

            <?php } elseif (isset($_SESSION['usr_id'])) { ?>
                <li><a href="../interfaces/fitness_page.php">Fitness</a></li>
                <li><a href="../interfaces/diet_page_name.php">Diet</a></li>
                <li><a href="../interfaces/about_us.php">About Us</a></li>
                <li><a href="../interfaces/user_profile.php">Profile</a></li>
                <li><a href="../interfaces/authentication.php">Log Out</a></li>
            <?php } elseif (isset($_SESSION['adm_id'])) { ?>
                <li><a href="../interfaces/admin_user_management.php">Users</a></li>
                <li><a href="../interfaces/adminfitnesstable.php">Fitness</a></li>
                <li><a href="../interfaces/adminmealtable.php">Diet</a></li>
                <li><a href="../interfaces/feedback.php">Feedback</a></li>
                <li><a href="../interfaces/authentication.php">Log Out</a></li>
            <?php } ?>
        </ul>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const hamburger = document.getElementById('hamburger-btn');
        const sidebar = document.getElementById('sidebar');
        const closeBtn = document.getElementById('close-btn');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        if (hamburger && sidebar && closeBtn && sidebarOverlay) {
            hamburger.addEventListener('click', () => {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
            });
            closeBtn.addEventListener('click', () => {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }
    });
</script>