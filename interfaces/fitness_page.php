<?php
// NAME: Joan Chua Yong Xin
// Project name: Fitness Page

// DESCRIPTION OF PROGRAM:
// - This script generates the main Fitness Page in the NutriFit system for logged-in users.
// - It retrieves user-specific data such as name, height, gender, birthdate, latest weight log, profile picture, and calculates the BMI and BMI status. The background color of the BMI box
// dynamically changes based on the BMI category (Underweight, Normal, Overweight, Obese).
// - It also calculates the total calories burned for the current day from the workout session log.
// - The page dynamically displays a list of available workouts fetched from the database, including their images, names, MET values, estimated calories burned, and category. The calories burned
// for each workout are calculated using the user’s weight, age, gender factor, and workout intensity.
// - Users can filter workouts using a search bar or category dropdown, and click a card to begin a training session. Upon card click, the selected workout ID is stored via AJAX and redirects
// the user to fitness_session.php.

// FIRST WRITTEN: 18-06-2025
// LAST MODIFIED: 08-07-2025 


include ('../features/connection.php');

include ('../features/restriction.php');

include ('../features/embed.php'); 

$user_id = $_SESSION['usr_id'];

$query = "SELECT user_t.usr_name, user_t.usr_height,
                 profile_picture_t.pic_picture, uwl.weight_log_weight AS usr_weight
          FROM user_t
          INNER JOIN PROFILE_PICTURE_T ON user_t.pic_id = PROFILE_PICTURE_T.pic_id
          LEFT JOIN user_weight_log_t uwl ON uwl.weight_log_id = (
              SELECT weight_log_id 
              FROM user_weight_log_t 
              WHERE usr_id = user_t.usr_id 
              ORDER BY weight_log_date DESC 
              LIMIT 1
          )
          WHERE user_t.usr_id = ?";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $height, $picture, $weight);
$stmt->fetch();
$stmt->close();

$bmi = ($height > 0 && $weight > 0) ? round($weight / (($height / 100) ** 2), 1) : 0;

if ($bmi < 18.5) {
    $bmiStatus = "Underweight";
} elseif ($bmi < 24.9) {
    $bmiStatus = "Normal";
} elseif ($bmi < 29.9) {
    $bmiStatus = "Overweight";
} else {
    $bmiStatus = "Obese";
}

switch ($bmiStatus) {
    case "Underweight":
        $bmiColor = "#6EC1F3";
        break;
    case "Normal":
        $bmiColor = "#a6d44d";
        break;
    case "Overweight":
        $bmiColor = "#F3CE6E";
        break;
    case "Obese":
        $bmiColor = "#D35B50";
        break;
    default:
        $bmiColor = "#ccc";
}

// Calculate total calories burned today
$caloriesBurned = 0;
$todayDate = date('Y-m-d');

$burn_query = "SELECT IFNULL(SUM(wlog_calories_burned), 0) FROM user_workout_session_t 
               WHERE usr_id = ? AND DATE(wlog_timestamp) = ?";

$stmt = $connection->prepare($burn_query);
$stmt->bind_param("is", $user_id, $todayDate);
$stmt->execute();
$stmt->bind_result($totalBurned);
$stmt->fetch();
$stmt->close();

// Retrieval of gender and birthdate
$user_detail_query = "SELECT usr_gender, usr_birthdate FROM user_t WHERE usr_id = ?";
$stmt = $connection->prepare($user_detail_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($gender, $birthdate);
$stmt->fetch();
$stmt->close();
$_SESSION['usr_gender'] = $gender;
$_SESSION['usr_birthdate'] = $birthdate;

$today = new DateTime();
$birthDate = new DateTime($birthdate);
$age = $today->diff($birthDate)->y;

$genderFactor = ($gender === 'M') ? 1.0 : 0.95;
$ageFactor = ($age < 40) ? 1.0 : (($age >= 40 && $age < 50) ? 0.97 : (($age >= 50 && $age < 60) ? 0.94 : 0.91));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Fitness Page</title>
<link rel="stylesheet" href="../styles/fitness.css">

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
  <?php include '../features/header.php'; ?>

  <div class="search-box">
    <input type="text" id="searchInput" placeholder="Search" />
    <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
  </div>
<div class="shape-wrapper">
  <div class="gray-bg-box">
    <div class="slogan-box">
      <p class="slogan-text">
        DREAM IT,<br>
        WISH IT, DO IT
      </p>
    </div>
  </div>

  <div class="svg-wrapper">
      <div class="svg-container">
        <!-- Desktop Red Shape -->
        <svg viewBox="0 0 515 420" xmlns="http://www.w3.org/2000/svg" class="red-shape red-desktop">
          <path d="M 0 35 A 35 35 0 0 1 35 0 H 205 A 35 35 0 0 1 240 35 
                  v 195 A 35 35 0 0 0 275 260 H 475 A 35 35 0 0 1 515 290 
                  V 389 A 35 35 0 0 1 480 420 H 40 A 35 35 0 0 1 0 385 Z" fill="#D35B50"/>
        </svg>

        <!-- Mobile Red Shape (hidden on desktop) -->
        <svg viewBox="0 0 515 420" xmlns="http://www.w3.org/2000/svg" class="red-shape red-mobile">
          <path d="M 35 253 C 0 253 0 218 0 218 V 28 C 0 0 35 0 35 0 H 205 A 35 35 0 0 1 240 35 
                  v 130 A 35 35 0 0 0 280 200 H 475 A 35 35 0 0 1 506 233 
                  V 389 A 35 35 0 0 1 480 420 H 274 C 245 420 241 395 242 394 
                  V 281 V 282 C 231 254 214 253 201 254 Z" fill="#D35B50"/>
        </svg>

      <div class="svg-content">
        <?php $profileImg = 'data:image/jpeg;base64,' . base64_encode($picture); ?>
        <img src="<?php echo $profileImg; ?>" class="user-img" alt="Profile Picture" />
        <h3>Hi, Welcome<br><?php echo htmlspecialchars($name); ?></h3>

        <div class="btn-section">
          <button class="label-green">Fitness Choice</button>
          <div class="dropdown-wrapper">
            <select name="category" class="category-dropdown" id="categoryFilter">
              <option value="all" selected>Category</option>
              <?php
              $cat_query = "SELECT cate_name FROM category_t";
              $stmt = $connection->prepare($cat_query);
              $stmt->execute();
              $result = $stmt->get_result();
              while ($row = $result->fetch_assoc()) {
                echo '<option value="' . strtolower(trim($row['cate_name'])) . '">' . htmlspecialchars($row['cate_name']) . '</option>';
              }
              $stmt->close();
              ?>
            </select>
            <i class="fa-solid fa-angle-down custom-arrow"></i> 
          </div>
        </div>
      </div>
    </div>

    <div class="bmi-box" style="background-color: <?php echo $bmiColor; ?>;" data-weight="<?php echo $weight; ?>" data-height="<?php echo $height; ?>">
      <p>BMI Status<br><strong id="bmi-status"><?php echo $bmiStatus; ?></strong></p>
      <div class="bmi-circle" id="bmi-value" style="background-color: <?php echo $bmiColor; ?>;"><?php echo $bmi; ?></div>
    </div>

    <div class="right-section">
      <div class="kcal-box">
        <p><strong>Total Kcal Burned:</strong></p>
        <div class="kcal-circle">
          <div class="kcal-value"><?php echo $totalBurned; ?>Kcal</div>
          <i class="fa-solid fa-fire fire-icon"></i>
        </div>
      </div>

      <div class="meal-button" onclick="window.location.href='../interfaces/diet_page_name.php'">
        <button class="btn-meal">Meal Plans <i class="fa-solid fa-utensils"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="fitness-cards">
  <div id="no-results" style="display:none; text-align:center; font-weight:bold; margin-top:20px; text-size:1.3vw;">
    No matching workout found.
  </div>

  <?php
    $query = "SELECT w.work_id, w.work_name, w.work_description, w.work_MET, w.work_image, 
                    w.work_beginner, w.work_intense, c.cate_name 
              FROM workout_t w 
              LEFT JOIN category_t c ON w.cate_id = c.cate_id";
    $stmt = $connection->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
      // Base64 encode image
      $imgData = base64_encode($row['work_image']);
      $src = 'data:image/png;base64,' . $imgData;

      // Extract values
      $categoryKey = strtolower(trim($row['cate_name']));
      $MET = $row['work_MET'];
      $durationBeginner = $row['work_beginner'] / 60; // minutes to hours
      $durationIntense = $row['work_intense'] / 60;

      // Calories burned formula
      $beginnerKcal = round($MET * 0.75 * $weight * $durationBeginner * $genderFactor * $ageFactor);
      $intenseKcal = round($MET * 1.25 * $weight * $durationIntense * $genderFactor * $ageFactor);
  ?>
  <div class="card" 
          data-category="<?php echo $categoryKey; ?>" 
          data-work-id="<?php echo $row['work_id']; ?>" 
          style="text-decoration: none; color: inherit;">
        <div class="card-img-wrapper">
          <img src="<?php echo $src; ?>" class="card-img" alt="Workout Image">
          <button class="card-play"><i class="fa-solid fa-circle-play"></i></button>
        </div>
        <div class="card-body">
          <div class="card-top">
            <h4 class="card-title"><?php echo htmlspecialchars($row['work_name']); ?></h4>
            <span class="card-category"><?php echo htmlspecialchars($row['cate_name']); ?></span>
          </div>
          <p class="card-kcal"><i class="fa-solid fa-fire"></i> <?php echo $beginnerKcal . ' - ' . $intenseKcal; ?> Kcal</p>
        </div>
      </div>
  <?php }
  $stmt->close();
  ?>
</div>
<?php include '../features/footer.php'; ?>


<script>
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const cards = document.querySelectorAll('.fitness-cards .card');
    const noResults = document.getElementById('no-results');

    function filterCards() {
      const keyword = searchInput.value.toLowerCase().trim();
      const selectedCategory = categoryFilter.value.toLowerCase().trim();
      let found = false;

      cards.forEach(card => {
        const title = card.querySelector('.card-title').textContent.toLowerCase();
        const cardCategory = card.dataset.category.toLowerCase().trim();

        const matchesCategoryDropdown = selectedCategory === 'all' || cardCategory === selectedCategory;
        const matchesSearch = title.includes(keyword) || cardCategory.includes(keyword);

        const shouldShow = matchesCategoryDropdown && matchesSearch;
        card.style.display = shouldShow ? 'block' : 'none';
        if (shouldShow) found = true;
      });

      noResults.style.display = found ? 'none' : 'block';
    }

    searchInput.addEventListener('input', filterCards);
    categoryFilter.addEventListener('change', filterCards);

    // const toggleBtn = document.getElementById("menuToggle");
    // const sideMenu = document.getElementById("sideMenu");
    // const closeBtn = document.getElementById("closeMenu");

    // toggleBtn.addEventListener("click", () => {
    //   sideMenu.classList.toggle("active");
    // });

    // closeBtn.addEventListener("click", () => {
    //   sideMenu.classList.remove("active");
    // });


  document.querySelectorAll('.fitness-cards .card').forEach(card => {
    card.addEventListener('click', function () {

      const workId = this.getAttribute('data-work-id');

      fetch('../features/store_work_id.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'work_id=' + encodeURIComponent(workId)
      })
      .then(response => response.json())
      .then(data => {
        console.log("HI");
        console.log(data);
        if (data.status === 'success') {
          window.location.href = 'fitness_session.php';
        } else {
          alert('Failed to load session.');
        }
      });
    });
  });

</script>

</body>
</html>