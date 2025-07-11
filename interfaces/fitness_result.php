<?php
// NAME: Joan Chua Yong Xin

// Project name: Fitness Result Page

// DESCRIPTION OF PROGRAM:
// - This script displays the Fitness Result page of the NutriFit system after a user completes a workout session.
// - It retrieves the most recent workout session for the logged-in user from the `user_workout_session_t` table, including workout name, 
// intensity level, and calories burned, by joining with the `workout_t` table.
// - The page includes a "Return Home Page" button to redirect users back to the Fitness main page.
// - It serves as a motivational summary and completion screen for users to reflect on their performance.

// FIRST WRITTEN: 25-06-2025
// LAST MODIFIED: 06-07-2025


include ('../features/connection.php');
include ('../features/embed.php'); 

$usr_id = $_SESSION['usr_id'];

$query = "SELECT s.work_id, s.wlog_intensity, s.wlog_calories_burned, w.work_name 
          FROM user_workout_session_t s 
          JOIN workout_t w ON s.work_id = w.work_id 
          WHERE s.usr_id = ? 
          ORDER BY s.wlog_timestamp DESC 
          LIMIT 1";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $usr_id);
$stmt->execute();
$stmt->bind_result($work_id, $intensity, $calories, $workout_name);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fitness Result</title>
  <link rel="stylesheet" href="../styles/fitness.css">
</head>
<body>
  <?php include ('../features/header.php'); ?>
  <!-- Mobile Header -->
  <!-- <div class="mobile-header">
    <div class="logo">NutriFit</div>
    <button class="hamburger" id="menuToggle">&#9776;</button>
  </div> -->

  <!-- Slide-out Mobile Menu -->
  <!-- <div class="side-menu" id="sideMenu">
    <button class="close-btn" id="closeMenu">&times;</button>
    <a href="fitness_page.php">Fitness</a>
    <a href="#">Diet</a>
    <a href="#">About Us</a>
    <a href="#">Profile</a>
    <a href="#">Log Out</a>
  </div> -->

  <div class="result-container">

    <!-- SVG banner now inside -->
    <div class="svg-banner">
      <svg class="resultBackground" viewBox="0 0 1900 600" preserveAspectRatio="none">
        <defs>
          <linearGradient id="bannerGradient" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#EBB3AD" />
            <stop offset="100%" stop-color="#D35B50" />
          </linearGradient>
        </defs>
        <path d="M 0 251 L 1906 0 L 1905 310 L 0 555 Z" fill="url(#bannerGradient)" />
      </svg>
    </div>

    <div class="svg-bottom-patch">
      <svg class="patchSVG" viewBox="0 0 1910 800" preserveAspectRatio="none">
        <path d="M 0 897 L 1910 848 L 1912 258 L 0 528 Z" fill="#FAFBF5" />
      </svg>
    </div>

    <div class="mobile-triangle-svg">
      <svg class="triangleSVG" viewBox="0 0 400 1600" preserveAspectRatio="none">
        <defs>
          <linearGradient id="triangleGradient" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="rgba(235, 179, 173, 1)" />
            <stop offset="100%" stop-color="rgba(211, 91, 80, 1)" />
          </linearGradient>
        </defs>
        <path d="M 0 1600 L 400 1600 L 400 500 L 0 1600 Z" fill="url(#triangleGradient)" />
      </svg>
    </div>

    <!-- Top Content -->
    <div class="result-left">
      <h1 class="workout-name"><?php echo htmlspecialchars($workout_name); ?></h1>
      <div class="intensity"><?php echo htmlspecialchars($intensity); ?></div>
      <div class="status">Training Finished</div>
    </div>

    <div class="desktop-banner-container">
      <h1 class="desktop-banner-text">CONGRATULATIONS</h1>
      <img src="../images/Result.png" class="desktop-athlete-img" alt="Athlete" />
    </div>

    <!-- Bottom Content -->
    <div class="burned-info">
      <div class="burned-title">BURNED <i class="fa-solid fa-fire fire-icon1"></i></div>
      <div class="burned-kcal"><?php echo (int)$calories; ?> Kcal</div>
    </div>

    <div class="mobile-banner-container">
      <img src="Result.png" class="mobile-athlete-img" alt="Athlete" />
    </div>

    <form action="fitness_page.php" method="get">
      <button class="return-btn" type="submit">Return Home Page</button>
    </form>
  </div>
<?php include "../features/footer.php" ?>

</body>
</html>

