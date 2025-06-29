<?php
// session_start(); 
// $_SESSION['account_type'] = 'user';
include '../features/connection.php';

include '../features/header.php';

include '../features/embed.php'; 

$usr_id = 1; // Temporary fixed user

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

<body>
  <svg class="resultBackground" viewBox="0 0 1900 410" preserveAspectRatio="none">
  <defs>
    <linearGradient id="bannerGradient" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#EBB3AD" />
      <stop offset="100%" stop-color="#D35B50" />
    </linearGradient>
  </defs>
    <path d="M 0 260 L 1900 100 L 1900 260 L 0 410 Z" fill="url(#bannerGradient)" />
  </svg>

  <div class="result-container">
    <div class="result-left">
      <h1 class="workout-name"><?php echo htmlspecialchars($workout_name); ?></h1>
      <div class="intensity"><?php echo htmlspecialchars($intensity); ?></div>
      <div class="status">Training Finished</div>
    </div>

    <div class="banner-container">
      <h1 class="banner-text">CONGRATULATIONS</h1>
      <img src="Result.png" class="athlete-img" alt="Athlete" />
    </div>

    <div class="burned-info">
      <div class="burned-title">BURNED <i class="fa-solid fa-fire fire-icon"></i></div>
      <div class="burned-kcal"><?php echo (int)$calories; ?> Kcal</div>
    </div>

    <form action="fitness_page.php" method="get">
      <button class="return-btn" type="submit">Return Home Page</button>
    </form>
  </div>
</body>
</html>

<?php include "../features/footer.php" ?>
