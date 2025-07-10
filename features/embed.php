<!-- 
Name: Mr. Chung Yhung Yie
Project Name: embed.php
Description: to set teh page's font by linking to Google Fonts, includes the Font Awesome library for icons, links multiple custom CSS files 
    for sytling diffrent parts of the website, loads Chart.js library for creating charts and adds custom JavaSCript files to handle functionalities. 

First Written: 1/6/2025
Last Modified: 8/7/2025 
-->

<!-- WRITES THE FONT STYLES AND LINK TO CSS & JS -->

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

<!-- LINK TO style.css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


<link rel="stylesheet" href="../styles/header.css">
<link rel="stylesheet" href="../styles/footer.css">
<link rel="stylesheet" href="../styles/body.css">

<link rel="stylesheet" href="../styles/admin_dashboard_direc.css">
<link rel="stylesheet" href="../styles/admin_statistic_chart.css">
<link rel="stylesheet" href="../styles/sys_activity_log.css">
<link rel="stylesheet" href="../styles/update_profile.css">
<link rel="stylesheet" href="../styles/user_profile_acc.css">
<link rel="stylesheet" href="../styles/user_profile_charts.css">

<link rel="stylesheet" href="../styles/authentication.css">
<link rel="stylesheet" href="../styles/about_us.css">
<link rel="stylesheet" href="../styles/feedback.css">

<link rel="stylesheet" href="../styles/activity_history.css">
<link rel="stylesheet" href="../styles/admin_user_management.css">



<!-- LINK TO Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<!-- LINK TO javascript.js (defer = runs after html is fully loaded) -->
<script src="../javascript/user_profile.js" defer></script>
<script src="../javascript/admin_dashboard.js" defer></script>
<script src="../javascript/authentication.js" ></script>
<script src="../javascript/feedback.js" ></script>
<script src="../javascript/nutrition.js" defer></script>
