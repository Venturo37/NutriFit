<?php

// if (session_start() === PHP_SESSION_NONE) {
//     session_start();
// }

include('../features/connection.php');
include('../features/restriction.php');

date_default_timezone_set('Asia/Kuala_Lumpur');
$today_string = date('Y-m-d'); // Ensure this is defined early

// --- 1. GATHER ALL NECESSARY USER DATA AND CALCULATE GOALS ---

// Fetch comprehensive user data (name, gender, dob, height, pic_id).
$user_stmt = $connection->prepare("SELECT * FROM user_t WHERE usr_id = ?");
$user_stmt->bind_param("i", $_SESSION['usr_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch user's latest weight for calculations.
$weight_kg = 0;
$weight_stmt = $connection->prepare("SELECT weight_log_weight FROM user_weight_log_t WHERE usr_id = ? ORDER BY weight_log_date DESC LIMIT 1");
$weight_stmt->bind_param("i", $_SESSION['usr_id']);
$weight_stmt->execute();
$weight_result = $weight_stmt->get_result();
if ($weight_row = $weight_result->fetch_assoc()) {
    $weight_kg = $weight_row['weight_log_weight'];
}

// Store user's height.
$height_cm = $user['usr_height'];

// Calculate Age from Date of Birth.
$age = 0;
if (!empty($user['usr_dob'])) {
    $dob = new DateTime($user['usr_dob']);
    $today_dt = new DateTime($today_string);
    $age = $dob->diff($today_dt)->y;
}

// --- NEW: Calculate Daily Calorie Goal (TDEE) using Mifflin-St Jeor ---
$calorie_goal = 0;
if ($weight_kg > 0 && $height_cm > 0 && $age > 0) {
    // BMR = (10 × weight in kg) + (6.25 × height in cm) - (5 × age in years) + s
    // where s is +5 for males and -161 for females.
    $base_calories = (10 * $weight_kg) + (6.25 * $height_cm) - (5 * $age);
    
    if (strtolower($user['usr_gender']) == 'male') {
        $bmr = $base_calories + 5;
    } else { // Assumes female for any other value.
        $bmr = $base_calories - 161;
    }
    
    // TDEE = BMR × Activity Factor. Using 1.2 for sedentary as requested.
    $calorie_goal = round($bmr * 1.2);
}


// --- 2. CALCULATE TODAY'S ACTUAL INTAKE AND EXPENDITURE ---

/*

NAME : Mr. Chan Rui Jie 
PROJECT NAME : diet_page.php(User Daily Energy Expenditure)  
DESCRIPTION OF PROGRAM :  
    Calculate User's TDEE
FIRST WRITTEN : June 9th, 2025  
LAST MODIFIED : July 10th, 2025  

*/
$consumed = 0;

$consume_query = "SELECT SUM(calories) AS total_consumed FROM (
    SELECT 
        (m.meal_carbohydrates * 3 + m.meal_protein * 4 + m.meal_fats * 9) AS calories
    FROM user_meal_intake_t umi
    JOIN meal_t m ON umi.meal_id = m.meal_id
    WHERE umi.usr_id = ? 
      AND umi.mlog_timestamp >= CURDATE() 
      AND umi.mlog_timestamp < CURDATE() + INTERVAL 1 DAY

    UNION ALL

    SELECT 
        (mi.meal_carbohydrates * 3 + mi.meal_protein * 4 + mi.meal_fats * 9) AS calories
    FROM user_meal_intake_t umi
    JOIN manual_input_t mi ON umi.manual_id = mi.manual_id
    WHERE mi.usr_id = ? 
      AND umi.mlog_timestamp >= CURDATE() 
      AND umi.mlog_timestamp < CURDATE() + INTERVAL 1 DAY
) AS combined";

$consume_stmt = $connection->prepare($consume_query);
$consume_stmt->bind_param("ii", $_SESSION['usr_id'], $_SESSION['usr_id']);
$consume_stmt->execute();
$consume_result = $consume_stmt->get_result();

if ($consume_result && $row = $consume_result->fetch_assoc()) {
    $consumed = (int)($row['total_consumed'] ?? 0);
}

// Calculate total calories burned from exercise TODAY.
$burned = 0;
$burn_query = "SELECT SUM(wlog_calories_burned) AS session_calorie_burned FROM user_workout_session_t WHERE usr_id = ? AND DATE(wlog_timestamp) = ?";
$burn_stmt = $connection->prepare($burn_query);
$burn_stmt->bind_param("is", $_SESSION['usr_id'], $today_string);
$burn_stmt->execute();
$burn_result = $burn_stmt->get_result();

$used_actual_burn = false;

// if ($burn_result && $row = $burn_result->fetch_assoc() && !empty($row['session_calorie_burned'])) {
//     $burned = (int)$row['session_calorie_burned'];
//     $used_actual_burn = true;
// }

$row = ($burn_result) ? $burn_result->fetch_assoc() : false;
if ($row && !empty($row['session_calorie_burned'])) {
    $burned = (int)$row['session_calorie_burned'];
    $used_actual_burn = true;
}

// If no workout was logged today, estimate sedentary TDEE
if (!$used_actual_burn && $weight_kg > 0 && $height_cm > 0 && $age > 0) {
    $base_calories = (10 * $weight_kg) + (6.25 * $height_cm) - (5 * $age);
    
    if (strtolower($user['usr_gender']) == 'male') {
        $bmr = $base_calories + 5;
    } else {
        $bmr = $base_calories - 161;
    }

    // Assume sedentary activity level
    $burned = round($bmr * 1.2);
}

// Calculate BMI (based on latest weight).
$bmi = 0;
if ($height_cm > 0 && $weight_kg > 0) {
    $height_m = $height_cm / 100;
    $bmi = $weight_kg / ($height_m * $height_m);
}


// --- 3. FETCH DATA FOR UI ELEMENTS ---

// Fetch all meal options for display.
$meal_query = "SELECT * FROM meal_t";
$meal_result = mysqli_query($connection, $meal_query);
$meals = [];
if ($meal_result && mysqli_num_rows($meal_result) > 0) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    while ($row = mysqli_fetch_assoc($meal_result)) {
        if (!empty($row['meal_image'])) {
            $mime_type = $finfo->buffer($row['meal_image']);
            $row['meal_image'] = 'data:' . $mime_type . ';base64,' . base64_encode($row['meal_image']);
        }
        $meals[] = $row;
    }
}

// Fetch meal time options for the dropdown.
$meal_options = "SELECT * FROM meal_time_t";
$meal_options_result = mysqli_query($connection, $meal_options);
$meal_options_data = [];
if ($meal_options_result && mysqli_num_rows($meal_options_result) > 0) {
    while ($row = mysqli_fetch_assoc($meal_options_result)) {
        $meal_options_data[] = $row;
    }
}

// Fetch profile picture.
$profile_src = 'https://placehold.co/100x100/EFEFEF/AAAAAA&text=No+Image'; // Placeholder
if (!empty($user['pic_id'])) {
    $profile_statement = $connection->prepare("SELECT pic_picture FROM profile_picture_t WHERE pic_id = ?");
    $profile_statement->bind_param("i", $user['pic_id']);
    $profile_statement->execute();
    $profile_statement->bind_result($profile_blob);
    if ($profile_statement->fetch()) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->buffer($profile_blob);
        $base64 = base64_encode($profile_blob);
        $profile_src = 'data:' . $mime_type . ';base64,' . $base64;
    } 
    $profile_statement->close();
}

?>

/*

NAME : Mr. Chan Rui Jie 
PROJECT NAME : diet_page.php 
DESCRIPTION OF PROGRAM :  
    Main Diet page for user interface.
FIRST WRITTEN : June 7th, 2025  
LAST MODIFIED : July 10th, 2025  

*/

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php 
    include('../features/embed.php'); 
    ?>
    <link rel="stylesheet" href="../styles/nutrition.css">
    


    <title>Nutrifit - Diet</title>
</head>
<body>
    <?php include ('../features/header.php'); ?>

    <div id="content">
        <div class="flex_container">
            <div class="dp_section_1">
                <form>
                    <div class="search">
                        <ion-icon class="search_icon" name="search-outline"></ion-icon>
                        <input type="search" class="search_input" placeholder="Search Meals...">
                    </div>
                </form>
                <div class="text_container">
                    <h1>
                        <span>DO</span>N'T
                        <br>
                        QU<span>IT</span>
                    </h1>
                </div>

                <button class="fitness_button" onclick="location.href='fitness_page.php'">
                    <i class="fa-solid fa-circle-chevron-left fitness_icon"></i>
                    <i class="fa-solid fa-dumbbell fitness_icon"></i>
                    <h1>Fitness</h1>
                </button>
            </div>

            <div class="dp_section_2">
                <div class="background_shapes">
                    <div class="shape2"></div>
                    <div class="shape"></div>
                </div>
                <div class="profile_container">
                    <div class="profile_pic">
                        <img src="<?php echo htmlspecialchars($profile_src); ?>" alt="Profile Picture" onerror="this.onerror=null;this.src='https://placehold.co/100x100/EFEFEF/AAAAAA&text=No+Image';">
                    </div>
                    <div class="name">
                        <h1>
                            Hi, Welcome Back
                            <br>
                            <?php echo htmlspecialchars($user['usr_name']); ?>
                        </h1>
                    </div>
                </div>

                <div class="status_container">
                    <div class="status">
                        <h1>
                            BMI Status
                            <br>
                            <span id="bmiText">Normal</span>
                        </h1>
                        <div class="circle" id="bmiCircle">
                            <h1 id="bmiValue" data-bmi="<?= $bmi ?>">0.0</h1>
                        </div>
                    </div>
                </div>

                <div class="chunk_1">
                    <div class="text_box"><h1>Today's Kcal Burned</h1></div>
                    <div class="kcal_box"><h1><span id="kcalBurnedText">0</span> Kcal</h1></div>
                </div>

                <div class="chunk_3">
                    <h1>Total Kcal Consumed</h1>
                    <div class="calorie_container">
                        <svg class="progress_ring" viewBox="0 0 100 100">
                            <circle class="bg_ring" cx="50" cy="50" r="45" />
                            <circle class="progress_ring_circle" cx="50" cy="50" r="45" />
                        </svg>
                        <div class="calorie_info">
                            <div id="kcalText"><?php echo $consumed; ?> Kcal</div>
                            <div class="flame_icon"><i class="fa-solid fa-fire-flame-curved"></i></div>
                        </div>
                    </div>
                    <button type="button" class="input_button">Manual Input</button>
                </div>

                <div class="chunk_2">
                    <h1>Diet Choice</h1>
                    <div class="dropdown">
                        <div class="select">
                            <span class="selected">Options</span>
                            <div class="caret"></div>
                        </div>
                        <ul class="menu">
                            <?php foreach ($meal_options_data as $option): ?>
                                <li class="<?php echo ($option['mltm_id'] == 1) ? 'active' : ''; ?>" data-meal-time="<?php echo htmlspecialchars($option['mltm_id']); ?>">
                                    <?php echo htmlspecialchars($option['mltm_period']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="dp_section_3">
                <?php if (!empty($meals)): ?>
                    <?php foreach ($meals as $meal): 
                        $meal_id = htmlspecialchars($meal['meal_id']);
                        $meal_name = htmlspecialchars($meal['meal_name']);
                        $meal_kcal = $meal['meal_carbohydrates'] * 3 + $meal['meal_protein'] * 4 + $meal['meal_fats'] * 9;
                    ?>
                        <form action="selected_meal.php" method="POST" class="card_form">
                            <input type='hidden' name='meal_id' value='<?= $meal_id ?>'/>
                            <div class="card">
                                <img src="<?= htmlspecialchars($meal['meal_image']); ?>" alt="Meal image" onerror="this.onerror=null;this.src='https://placehold.co/200x150/EFEFEF/AAAAAA&text=No+Image';">
                                <div class="card_button"><?= $meal_name ?></div>
                                <div class="kcal_intake">
                                    <div class="flame_icon"><i class="fa-solid fa-fire-flame-curved"></i></div>
                                    <?= round($meal_kcal) ?> Kcal
                                </div>
                            </div>
                        </form>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="width: 100%; text-align: center;">No meals found for this category.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include ('manual_input.php'); ?>
    <footer><?php include ('../features/footer.php'); ?></footer>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Existing calorie/BMI logic ---
        const totalCaloriesConsumed = <?php echo $consumed; ?>;
        const calorieGoal = <?php echo $calorie_goal; ?>;
        const totalCaloriesBurned = <?php echo $burned; ?>;
        const bmiValue = <?php echo json_encode($bmi); ?>;

        updateCalories(totalCaloriesConsumed, calorieGoal > 0 ? calorieGoal : 2000);
        updateBMI(bmiValue);

        const burnedText = document.getElementById('kcalBurnedText');
        if (burnedText) {
            burnedText.textContent = totalCaloriesBurned;
        }

        const kcalText = document.getElementById('kcalText');
        if (kcalText) {
            kcalText.textContent = `${totalCaloriesConsumed} Kcal`;
        }

        // --- NEW: Make entire card clickable ---
        const cards = document.querySelectorAll('.card_form .card');
        cards.forEach(card => {
            card.addEventListener('click', () => {
                card.closest('form').submit();
            });
        });
        refreshCalorieStats();
    });
</script>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
</body>
</html>
