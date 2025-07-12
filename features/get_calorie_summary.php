<?php
/*
NAME : Mr. Chan Rui Jie 
PROJECT NAME : get_calorie_summary.php
DESCRIPTION OF PROGRAM :  
    PHP that contains all calculation such as BMI, TDEE, KCAL burned/consumed and encode them in JSON. 
FIRST WRITTEN : June 9th, 2025  
LAST MODIFIED : July 11th, 2025  
*/
include('connection.php');

error_reporting(0); // Suppress warnings that could corrupt JSON
header('Content-Type: application/json'); // Ensure correct response type
date_default_timezone_set('Asia/Kuala_Lumpur');
$today_string = date('Y-m-d');

$usr_id = $_SESSION['usr_id'] ?? 0;

$consumed = 0;
$burned = 0;
$goal = 2000;
$bmi = 0;

// Query consumption
$consume_query = "SELECT SUM(calories) AS total_consumed FROM (
    SELECT 
        (m.meal_carbohydrates * 3 + m.meal_protein * 4 + m.meal_fats * 9) AS calories
    FROM user_meal_intake_t umi
    JOIN meal_t m ON umi.meal_id = m.meal_id
    WHERE umi.mlog_timestamp >= CURDATE() 
      AND umi.mlog_timestamp < CURDATE() + INTERVAL 1 DAY
      AND umi.usr_id = ?

    UNION ALL

    SELECT 
        (mi.meal_carbohydrates * 3 + mi.meal_protein * 4 + mi.meal_fats * 9) AS calories
    FROM user_meal_intake_t umi
    JOIN manual_input_t mi ON umi.manual_id = mi.manual_id
    WHERE umi.mlog_timestamp >= CURDATE() 
      AND umi.mlog_timestamp < CURDATE() + INTERVAL 1 DAY
      AND mi.usr_id = ?
) AS combined";

$consume_stmt = $connection->prepare($consume_query);
$consume_stmt->bind_param("ii", $usr_id, $usr_id);
$consume_stmt->execute();
$consume_result = $consume_stmt->get_result();

if ($consume_result && $row = $consume_result->fetch_assoc()) {
    $consumed = (int)($row['total_consumed'] ?? 0);
}


// Query burn
$burn_query = "SELECT SUM(wlog_calories_burned) AS burned FROM user_workout_session_t WHERE usr_id = ? AND DATE(wlog_timestamp) = ?";
$stmt2 = $connection->prepare($burn_query);
$stmt2->bind_param("is", $usr_id, $today_string);
$stmt2->execute();
$result2 = $stmt2->get_result();
if ($row2 = $result2->fetch_assoc()) {
    $burned = (int)($row2['burned'] ?? 0);
}

// Get user data for goal & bmi
$user = $connection->query("SELECT * FROM user_t WHERE usr_id = $usr_id")->fetch_assoc();
$height_cm = $user['usr_height'];
$dob = new DateTime($user['usr_dob']);
$age = $dob->diff(new DateTime($today_string))->y;
$weight_row = $connection->query("SELECT weight_log_weight FROM user_weight_log_t WHERE usr_id = $usr_id ORDER BY weight_log_date DESC LIMIT 1")->fetch_assoc();
$weight_kg = $weight_row['weight_log_weight'];

if ($height_cm > 0 && $weight_kg > 0) {
    $height_m = $height_cm / 100;
    $bmi = $weight_kg / ($height_m * $height_m);
}

// Goal (TDEE)
if ($weight_kg > 0 && $height_cm > 0 && $age > 0) {
    $base = 10 * $weight_kg + 6.25 * $height_cm - 5 * $age;
    $goal = strtolower($user['usr_gender']) == 'male' ? $base + 5 : $base - 161;
    $goal = round($goal * 1.2);
}

echo json_encode([
    'consumed' => $consumed,
    'burned' => $burned,
    'goal' => $goal,
    'bmi' => round($bmi, 1)
]);
?>
