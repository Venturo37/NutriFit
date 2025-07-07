<?php
include('../features/connection.php');

include('../features/restriction.php');

$usr_id = $_SESSION['usr_id'];

function getUserData ($connection, $usr_id) {
    $user_query = $connection->prepare(
        "SELECT usr_name, usr_birthdate, usr_gender, usr_height, pic_id 
        FROM user_t 
        WHERE usr_id = ?"
    );
// data type:
// i = integer
// s = string
// d = double
// b = blob
// bind_param safely bind variables to the placeholders (?) in an SQL query
    $user_query->bind_param("i", $usr_id);
    $user_query->execute();
    $user = $user_query->get_result()->fetch_assoc();
    $user_query->close();
// $user = [
//     'usr_name' => 'John Doe',
//     'usr_age' => '2003-08-27',
//     'usr_gender' => 'M',
//     'usr_height' => 180.2,
//     'pic_id' => 2
// ];
    return $user;
}


function getProfilePic ($connection, $pic_id) {
    $profile_src = '';
    $profile_statement = $connection->prepare(
        "SELECT pic_picture 
        FROM profile_picture_t 
        WHERE pic_id = ?"
    );
    // $profile_statement->bind_param("i", $user['pic_id']);
    $profile_statement->bind_param("i", $pic_id);
    $profile_statement->execute();
    $profile_blob = null;
    $profile_statement->bind_result($profile_blob);
    // STORES results into $profile_blob
    if ($profile_statement->fetch()) {
        // Identify the Multipurpose Internet Mail Extensions(MIME) type of the profile picture
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->buffer($profile_blob);
        $base64 = base64_encode($profile_blob);
        $profile_src = 'data:' . $mime_type . ';base64,' . $base64;
    } 
    $profile_statement->close();
    return $profile_src;
}


function getUserWeightLogs ($connection, $usr_id) {
// // Sample data for user_weight_log_t table
// // | weight_log_id | usr_id| weight_log_weight | weight_log_date     |
// // | --------------| ------| ------------------| ------------------- |
// // | 1             | 1     | 70                | 2025-06-06 06:43:31 |
// // | 3             | 1     | 71                | 2025-06-06 06:50:31 |
// // | 4             | 1     | 75                | 2025-06-06 07:43:31 |
// // | 5             | 1     | 80                | 2025-08-06 06:43:31 |
    $weight_query = $connection->prepare(
        "SELECT weight_log_weight, DATE(weight_log_date) AS weight_log_date
        FROM user_weight_log_t 
        WHERE usr_id = ? AND weight_log_date IN (
            SELECT MAX(weight_log_date) FROM user_weight_log_t 
            WHERE usr_id = ?
            GROUP BY DATE(weight_log_date)
        )
        ORDER BY weight_log_date ASC"
    );
    $weight_query->bind_param("ii", $usr_id, $usr_id);
    $weight_query->execute();
    $weight_result = $weight_query->get_result();

    $raw_weight_logs = [];
    while ($row = $weight_result->fetch_assoc()) {
        // $date_label = date('Y-m-d', strtotime($row['weight_log_date']));    // Format the date to 'Y-m-d' (from date string in row['weight_log_date'] to numberic Unix timestamp)
        $raw_weight_logs[$row['weight_log_date']] = floatval($row['weight_log_weight']);
    }
    $weight_query->close();
// // $raw_weight_logs = [
// //   '2025-06-06' => 75,
// //   '2025-08-06' => 80
// // ];
    return $raw_weight_logs;
}


function getUserWorkoutData ($connection, $usr_id) {
    $workout_query = $connection->prepare(
        "SELECT DATE(wlog_timestamp) AS wlog_timestamp, 
            SUM(wlog_calories_burned) AS total_kcal_burned
        FROM user_workout_session_t
        WHERE usr_id = ?
        GROUP BY DATE(wlog_timestamp)
        ORDER BY DATE(wlog_timestamp) ASC"
    );
    $workout_query->bind_param("i", $usr_id);
    $workout_query->execute();
    $workout_result = $workout_query->get_result();
    $raw_workout_data = [];
    while ($row = $workout_result->fetch_assoc()) {
        $raw_workout_data[$row["wlog_timestamp"]] = floatval($row['total_kcal_burned']);
    }
    $workout_query->close();
// $raw_workout_data = [
//     '2025-06-06' => 300.0,
//     '2025-06-07' => 500.0,
//     '2025-06-08' => 400.0,
// ];
    return $raw_workout_data;
}


function getUserMealData($connection, $usr_id) {
    $meal_query = $connection->prepare(
        "SELECT DATE(mlog_timestamp) AS mlog_timestamp, 
            SUM(
                IFNULL(meal.meal_carbohydrates, manual_input.meal_carbohydrates) * 3 +
                IFNULL(meal.meal_protein, manual_input.meal_protein) * 4 +
                IFNULL(meal.meal_fats, manual_input.meal_fats) * 9
            ) AS total_kcal_intake
        FROM user_meal_intake_t user_intake LEFT JOIN meal_t meal ON user_intake.meal_id = meal.meal_id
        LEFT JOIN manual_input_t manual_input ON user_intake.manual_id = manual_input.manual_id
        WHERE ( 
            (user_intake.usr_id = ? AND user_intake.manual_id IS NULL) 
            OR 
            (manual_input.usr_id = ? AND user_intake.manual_id IS NOT NULL)
        )
        GROUP BY DATE(mlog_timestamp)"
    );
    $meal_query->bind_param("ii", $usr_id, $usr_id);
    $meal_query->execute();
    $meal_result = $meal_query->get_result();
    $meal_data = [];
    while ($row = $meal_result->fetch_assoc()) {
        $meal_data[$row["mlog_timestamp"]] = floatval($row['total_kcal_intake']);
    }
    $meal_query->close();
    // $meal_data = [
    //     '2025-06-06' => 1800.0,
    //     '2025-06-07' => 2100.0,
    //     '2025-06-08' => 1500.0,
    // ];
    return $meal_data;
}


function calculateEstimatedWeight($raw_weight_logs, $workout_data, $meal_data, $user, $all_dates) {
    $weight_logs = [];
    $daily_tdee_values = [];
    $tdee = null;
    $current_weight = null;

    $birthdate = new DateTime($user['usr_birthdate']);
    $today = new DateTime();
    $age = $birthdate->diff($today)->y; // Calculate age in years
    $gender = $user['usr_gender'];
    $height = $user['usr_height'];   

    foreach ($all_dates as $date) {
        if (isset($raw_weight_logs[$date])){
            $current_weight = $raw_weight_logs[$date];

            $bmr = ($gender === 'M') ? 
                ((10 * $current_weight) + (6.25 * $height) - (5 * $age) + 5)
                : ((10 * $current_weight) + (6.25 * $height) - (5 * $age) - 161);
            $tdee = $bmr * 1.2; // Assuming sedentary activity level (no exercise / Office worker)
        }

        if ($tdee !== null) {
            $daily_tdee_values[$date] = $tdee;
        }

        if ($current_weight === null || $tdee == null) {
            continue;
        }

        $has_activity = isset($workout_data[$date]) || isset($meal_data[$date]);

        if ($has_activity) {
            $meal_kcal = $meal_data[$date] ?? 0;
            $workout_kcal = $workout_data[$date] ?? 0;

            $kcal_balance = $tdee + $workout_kcal - $meal_kcal;
            $weight_diff = $kcal_balance / 7700; // 1 kg of body weight is approximately 7700 kcal
            $current_weight = round($current_weight - $weight_diff, 2); // Round to 2 decimal places
        }
        
        $weight_logs[$date] = $current_weight; // Round to 2 decimal places
    }


    if (count($weight_logs) === 1) {
        $date = array_key_first($weight_logs);
        $weight = $weight_logs[$date];

        $weight_logs = [
            '' => $weight,
            $date => $weight
        ];
    }

    return ['weight_logs' => $weight_logs, 'daily_tdee_values' => $daily_tdee_values];
}




function getActivityDateRange($connection, $usr_id) {
    $activity_query = $connection->prepare(
        "SELECT MIN(earliest) AS earliest_date, MAX(latest) AS latest_date
        FROM (
            SELECT MIN(DATE(weight_log_date)) AS earliest, MAX(DATE(weight_log_date)) AS latest
            FROM user_weight_log_t 
            WHERE usr_id = ?
        UNION ALL
            SELECT MIN(DATE(wlog_timestamp)) AS earliest, MAX(DATE(wlog_timestamp)) AS latest
            FROM user_workout_session_t 
            WHERE usr_id = ?
        UNION ALL
            SELECT MIN(DATE(mlog_timestamp)) AS earliest, MAX(DATE(mlog_timestamp)) AS latest
            FROM user_meal_intake_t 
            WHERE usr_id = ? AND manual_id IS NULL
        UNION ALL
            SELECT MIN(DATE(mlog_timestamp)) AS earliest, MAX(DATE(mlog_timestamp)) AS latest
            FROM user_meal_intake_t JOIN manual_input_t ON user_meal_intake_t.manual_id = manual_input_t.manual_id
            WHERE manual_input_t.usr_id = ?
        ) AS combined"
    );    
    $activity_query->bind_param("iiii", $usr_id, $usr_id, $usr_id, $usr_id);
    $activity_query->execute();
    $activity_result = $activity_query->get_result();
    $row = $activity_result->fetch_assoc();
    $activity_result->close();

    return [
        'earliest_date' => $row['earliest_date'] ?? date('Y-m-d'),
        'latest_date' => $row['latest_date'] ?? date('Y-m-d')
    ];
}


$user = getUserData ($connection, $usr_id);
$profile_src = getProfilePic ($connection, $user['pic_id']);

$activity_date_range = getActivityDateRange($connection, $usr_id);
$earliest_activity_date = $activity_date_range['earliest_date'];
$latest_activity_date = $activity_date_range['latest_date'];

$today_str = date('Y-m-d');
if (strtotime($latest_activity_date) < strtotime($today_str)) {
    $latest_activity_date = $today_str;
}

$raw_weight_logs = getUserWeightLogs($connection, $usr_id);
$workout_data = getUserWorkoutData($connection, $usr_id);
$meal_data = getUserMealData($connection, $usr_id);

$start_date = new DateTime($earliest_activity_date);
$end_date = new DateTime($latest_activity_date);

$all_dates = [];
$period = new DatePeriod(
    $start_date,
    new DateInterval('P1D'), // 1 day interval
    $end_date->modify('+1 day') // Include the end date
);
foreach ($period as $date) {
    $all_dates[] = $date->format('Y-m-d');
}

$estimated_weight_data = calculateEstimatedWeight($raw_weight_logs, $workout_data, $meal_data, $user, $all_dates);
$estimated_weight_logs = $estimated_weight_data['weight_logs'];
$daily_tdee_values = $estimated_weight_data['daily_tdee_values'];


$start_year = (new DateTime($earliest_activity_date))->format('Y');
$start_month = (new DateTime($earliest_activity_date))->format('m');
$current_year = (new DateTime($latest_activity_date))->format('Y');
$current_month = (new DateTime($latest_activity_date))->format('m');

$selected_year = isset($_POST['year']) ? intval($_POST['year']) : intval($current_year);
$selected_month = isset($_POST['month']) ? intval($_POST['month']) : intval($current_month);

$selected_kcal_year = isset($_POST['kcal_year']) ? intval($_POST['kcal_year']) : intval($current_year);
$selected_kcal_month = isset($_POST['kcal_month']) ? intval($_POST['kcal_month']) : intval($current_month);

// AJAX FOR WEIGHT CHART
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_weights'])) {
    $year = intval($_POST['year']) ?: date('Y');
    $month = intval($_POST['month']) ?: date('m');

    $today_year = intval(date('Y'));
    $today_month = intval(date('m'));
    $today_day = intval(date('j'));
    $num_days = ($year === $today_year && $month === $today_month) ? $today_day : cal_days_in_month(CAL_GREGORIAN, $month, $year);
        // CALCULATE the days in the month of that YEAR

    $labels = [];
    $weights = [];
    $first_weight = null;
    $last_weight = null;

    for ($day = 1; $day <= $num_days; $day++) {
        $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $weight = $estimated_weight_logs[$date_str] ?? null;

        if ($weight !== null) {
            if ($first_weight === null) {
                $first_weight = $weight;
                $labels[] = '';
                $weights[] = $first_weight;
            }

            $labels[] = (string)$day;
            $weights[] = $weight;
            $last_weight = $weight;
        }
    }

    if ($first_weight !== null) {
        $labels[] = '';
        $weights[] = $last_weight;
    }

    header('Content-Type: application/json');
    echo json_encode(['labels' => $labels, 'weights' => $weights]);
    exit();
}
// AJAX FOR KCAL CHART
if ($_SERVER['REQUEST_METHOD'] === 'POST'&& isset($_POST['fetch_kcal_burned'])) {
    $year = intval($_POST['year']) ?: date('Y');
    $month = intval($_POST['month']) ?: date('m');

    $today_year = intval(date('Y'));
    $today_month = intval(date('m'));
    $today_day = intval(date('j'));

    if ($year === $today_year && $month === $today_month) {
        $num_day_display = $today_day;
    } else {
        $num_day_display = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        // CALCULATE the days in the month of that YEAR
    }

    $labels = [];
    $values = [];
    for ($day = 1; $day <= $num_day_display; $day++) {
        $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $labels[] = $day;
        $values[] = $workout_data[$date_str] ?? 0;
    }

    header('Content-Type: application/json');
    echo json_encode(['labels' => $labels, 'kcal_burned' => $values]);
    exit();
}

// AJAX FOR MEAL INTAKE CHART
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_meal_intake'])) {
    $year = intval($_POST['year']) ?: date('Y');
    $month = intval($_POST['month']) ?: date('m');

    $today_year = intval(date('Y'));
    $today_month = intval(date('m'));
    $today_day = intval(date('j'));

    if ($year === $today_year && $month === $today_month) {
        $num_day_display = $today_day;
    } else {
        $num_day_display = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        // CALCULATE the days in the month of that YEAR
    }

    $labels = [];
    $deficit_values = [];
    $tdee_values = [];
    $surplus_values = [];
    for ($day = 1; $day <= $num_day_display; $day++) {
        $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $labels[] = $day;

        $tdee_for_day = $daily_tdee_values[$date_str] ?? 0;
        $workout_kcal_for_day = $workout_data[$date_str] ?? 0;
        $meal_kcal_for_day = $meal_data[$date_str] ?? 0;

        $total_tdee_for_day = $tdee_for_day + $workout_kcal_for_day;

        $deficit = 0;
        $remaining_tdee = 0;
        $surplus = 0;

        if ($meal_kcal_for_day < $total_tdee_for_day) {
            $deficit = $meal_kcal_for_day;
            $remaining_tdee = $total_tdee_for_day - $meal_kcal_for_day;
        } else {
            $surplus = $meal_kcal_for_day - $total_tdee_for_day;
            $remaining_tdee = $total_tdee_for_day;
        }

        $deficit_values[] = $deficit;
        $tdee_values[] = $remaining_tdee;
        $surplus_values[] = $surplus;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'labels' => $labels,
        'deficit_values' => $deficit_values,
        'tdee_values' => $tdee_values,
        'surplus_values' => $surplus_values
    ]);
    exit();
}

$today_year = intval(date('Y'));
$today_month = intval(date('m'));
$today_day = intval(date('j'));

$num_days = ($selected_year === date('Y') && $selected_month === date('m')) ? $today_day : cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);

$init_labels = [];
$init_weights = [];
$first_weight = null;
$last_weight = null;

for ($day = 1; $day <= $num_days; $day++) {
    $date_str = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $day);
    $weight = $estimated_weight_logs[$date_str] ?? null;

    if ($weight !== null) {
        if ($first_weight === null) {
            $first_weight = $weight;
            $init_labels[] = '';
            $init_weights[] = $first_weight;
        }

        $init_labels[] = (string)$day;
        $init_weights[] = $weight;
        $last_weight = $weight;
    }
}
if ($first_weight !== null) {
    $init_labels[] = '';
    $init_weights[] = $last_weight;
}


$init_kcal_labels = [];
$init_kcal_values = [];


if ($selected_kcal_year === $today_year && $selected_kcal_month === $today_month) {
    $init_num_day_display = $today_day;
} else {
    $init_num_day_display = cal_days_in_month(CAL_GREGORIAN, $selected_kcal_month, $selected_kcal_year);

}
for ($day = 1; $day <= $init_num_day_display; $day++) {
    $date_str = sprintf('%04d-%02d-%02d', $selected_kcal_year, $selected_kcal_month, $day);
    $init_kcal_labels[] = $day;
    $init_kcal_values[] = $workout_data[$date_str] ?? 0;
}

$init_meal_intake_labels = [];
$init_deficit_values = [];
$init_tdee_values = [];
$init_surplus_values = [];

for ($day = 1; $day <= $init_num_day_display; $day++) {
    $date_str = sprintf('%04d-%02d-%02d', $selected_kcal_year, $selected_kcal_month, $day);
    $init_meal_intake_labels[] = $day;

    $tdee_for_day = $daily_tdee_values[$date_str] ?? 0;
    $meal_kcal_for_day = $meal_data[$date_str] ?? 0;
    $workout_kcal_for_day = $workout_data[$date_str] ?? 0;

    $total_tdee_for_day = $tdee_for_day + $workout_kcal_for_day;

    $deficit = 0;
    $remaining_tdee = 0;
    $surplus = 0;

    if ($meal_kcal_for_day < $total_tdee_for_day) {
        $deficit = $meal_kcal_for_day;
        $remaining_tdee = $total_tdee_for_day - $meal_kcal_for_day;
    } else {
        $surplus = $meal_kcal_for_day - $total_tdee_for_day;
        $remaining_tdee = $total_tdee_for_day;
    }

    $init_deficit_values[] = $deficit;
    $init_tdee_values[] = $remaining_tdee;
    $init_surplus_values[] = $surplus;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <?php include('../features/embed.php'); ?>
    <script src="../javascript/update_profile.js" defer></script>
    
</head>
<body>
    <?php include('../features/header.php'); ?>
    <div id="content">
        <div class="account">
            <div class="user_profile">
                <div class="profile_pic">
                    <img src="<?php echo $profile_src ?>" alt="Profile Picture">
                </div>
                <div class="name">
                    <h1>
                        <?php echo $user['usr_name'] ?></h1>
                    <p>PROFILE</p>
                </div>
            </div>
            <button class="update_profile_btn">
                Update Profile
            </button>
        </div>
        <div id="update_container" class="update_container" style="display: none;">
            <?php include('update_profile.php');?>
        </div>
        <div class="weight_section">
            <div class="weight title">
                <h2>Weight Chart (KG)</h2>
            </div>
            <form id="weight_chart_filer_form">
                <label for="month_select">Month:</label>
                <select id="month_select" name="month">
                    <?php
                        $selected_year = isset($_POST['year']) ? intval($_POST['year']) : $current_year;

                        $month_start = ($selected_year == $start_year) ? $start_month : 1;
                        $month_end = ($selected_year == $current_year) ? $current_month : 12;

                        for ($m = $month_end; $m >= $month_start; $m--) {
                            $selected = ($m == intval($current_month)) ? 'selected' : '';
                            printf("<option value='%02d' %s>%s</option>", 
                            $m, $selected, date('F', mktime(0,0,0, $m, 1)));
                        }
                    ?>
                </select>
                <label for="year_select">Year:</label>
                <select id="year_select" name="year" >
                    <?php
                        for ($y = $current_year; $y >= $start_year; $y--) {
                            $selected = ($y == intval($current_year)) ? 'selected' : '';
                            printf("<option value='%d' %s>%d</option>", $y, $selected, $y);
                        }
                    ?>
                </select>
            </form>
            <div class="weight_chart">
                <canvas id="weightChart"></canvas>
            </div>
        </div>
        <div class="kcal_section">
            <div class="kcal title">
                <h2>Calories Burned</h2>
                <button class="view_activity_history" onclick="window.location.href='../interfaces/fitness_history.php'">View Activity History
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
            <form id="kcal_chart_filer_form">
                <label for="kcal_month_select">Month:</label>
                <select id="kcal_month_select" name="month">
                    <?php          
                        $kcal_month_start = ($selected_kcal_year == $start_year) ? $start_month : 1;
                        $kcal_month_end = ($selected_kcal_year == $current_year) ? $current_month : 12;

                        for ($m = $kcal_month_end; $m >= $kcal_month_start; $m--) {
                            $selected = ($m == intval($selected_kcal_month)) ? 'selected' : '';
                            printf("<option value='%02d' %s>%s</option>", 
                            $m, $selected, date('F', mktime(0,0,0, $m, 1)));
                        }
                    ?>
                </select>
                <label for="kcal_year_select">Year:</label>
                <select id="kcal_year_select" name="year" >
                    <?php
                        for ($y = $current_year; $y >= $start_year; $y--) {
                            $selected = ($y == intval($selected_kcal_year)) ? 'selected' : '';
                            printf("<option value='%d' %s>%d</option>", $y, $selected, $y);
                        }
                    ?>
                </select>
            </form>
            <div class="kcal_chart">
                <canvas id="kcalChart"></canvas>
            </div>
        </div>
        <div class="meal_intake_section">
            <div class="meal_intake title">
                <h2>Meal Intake (Kcal)</h2>
                <button class="view_nutrition_intake_history" onclick="window.location.href='../interfaces/nutrition_history.php'">View Nutrition Intake History
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
            <form id="meal_intake_chart_filter_form">
                <label for="meal_intake_month_select">Month:</label>
                <select id="meal_intake_month_select" name="month">
                    <?php
                        $meal_month_start = ($selected_kcal_year == $start_year) ? $start_month : 1;
                        $meal_month_end = ($selected_kcal_year == $current_year) ? $current_month : 12;

                        for ($m = $meal_month_end; $m >= $meal_month_start; $m--) {
                            $selected = ($m == intval($selected_kcal_month)) ? 'selected' : '';
                            printf("<option value='%02d' %s>%s</option>", 
                            $m, $selected, date('F', mktime(0,0,0, $m, 1)));
                        }
                    ?>
                </select>
                <label for="meal_intake_year_select">Year:</label>
                <select id="meal_intake_year_select" name="year" >
                    <?php
                        for ($y = $current_year; $y >= $start_year; $y--) {
                            $selected = ($y == intval($selected_kcal_year)) ? 'selected' : '';
                            printf("<option value='%d' %s>%d</option>", $y, $selected, $y);
                        }
                    ?>
                </select>
            </form>
            <div class="meal_intake_chart">
                <canvas id="mealIntakeChart"></canvas>
            </div>
        </div>
    </div>
    <?php include('../features/footer.php'); ?>


    <script>
        window.appConfig.isUser = <?php echo json_encode(isset($_SESSION['usr_id'])); ?>
        window.appConfig.isAdmin = <?php echo json_encode(isset($_SESSION['adm_id'])); ?>

        const init_weightChart_data = {
            labels: <?php echo json_encode($init_labels); ?>,
            weights: <?php echo json_encode($init_weights); ?>
        };


        const init_kcalChart_data = {
            labels: <?php echo json_encode($init_kcal_labels); ?>,
            kcal_burned: <?php echo json_encode($init_kcal_values); ?>
        };

        const init_mealIntakeChart_data = {
            labels: <?php echo json_encode($init_meal_intake_labels); ?>,
            deficit_values: <?php echo json_encode($init_deficit_values) ?>, 
            tdee_values: <?php echo json_encode($init_tdee_values) ?>,
            surplus_values: <?php echo json_encode($init_surplus_values) ?>
        };
    </script>
</body>
</html>