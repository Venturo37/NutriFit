<?php

/*

NAME : Mr. Chan Rui Jie 
PROJECT NAME : inserted_selected_meal.php
DESCRIPTION OF PROGRAM :  
    Insert both manually inputed meals and user selected pre-set meal into 
    their respective table and user_meal_intake_t(main table to calculate user kcal intake)
FIRST WRITTEN : June 9th, 2025  
LAST MODIFIED : July 10th, 2025  

*/

include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // if (session_start() === PHP_SESSION_NONE) {
    //     session_start();
    // }

    ob_clean();
    header('Content-Type: application/json');
    http_response_code(200); // ensure 200 on success
    include('connection.php');

    $data = json_decode(file_get_contents('php://input'), true);
    $meal_id = isset($data['meal_id']) ? (int)$data['meal_id'] : 0;
    $usr_id = $_SESSION['usr_id'] ?? null;

    if (!$usr_id || !$meal_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }

    // Insert new meal log
    $stmt = $connection->prepare("
        INSERT INTO user_meal_intake_t (meal_id, usr_id, manual_id, mlog_timestamp)
        VALUES (?, ?, NULL, NOW())
    ");
    $stmt->bind_param("ii", $meal_id, $usr_id);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database insert failed.']);
        $stmt->close();
        $connection->close();
        exit;
    }
    $stmt->close();

    // Now fetch updated calorie data
    $consumed = 0;
    $burned = 0;

    // 1. Get calories from meals
    $consume_query = "
        SELECT SUM(m.meal_carbohydrates * 4 + m.meal_protein * 4 + m.meal_fats * 9) AS total_consumed
        FROM user_meal_intake_t umi
        JOIN meal_t m ON umi.meal_id = m.meal_id
        WHERE umi.usr_id = ?
    ";
    $consume_stmt = $connection->prepare($consume_query);
    $consume_stmt->bind_param("i", $usr_id);
    $consume_stmt->execute();
    $consume_result = $consume_stmt->get_result();
    if ($consume_result && $row = $consume_result->fetch_assoc()) {
        $consumed += (int)$row['total_consumed'];
    }
    $consume_stmt->close();

    // 2. Get manual inputs
    $manual_query = "
        SELECT SUM(meal_carbohydrates * 4 + meal_protein * 4 + meal_fats * 9) AS manual_total
        FROM manual_input_t
        WHERE usr_id = ?
    ";
    $manual_stmt = $connection->prepare($manual_query);
    $manual_stmt->bind_param("i", $usr_id);
    $manual_stmt->execute();
    $manual_result = $manual_stmt->get_result();
    if ($manual_result && $row = $manual_result->fetch_assoc()) {
        $consumed += (int)$row['manual_total'];
    }
    $manual_stmt->close();

    // 3. Calories burned from workouts
    $burn_query = "
        SELECT SUM(wlog_calories_burned) AS session_calorie_burned
        FROM user_workout_session_t
        WHERE usr_id = ?
    ";
    $burn_stmt = $connection->prepare($burn_query);
    $burn_stmt->bind_param("i", $usr_id);
    $burn_stmt->execute();
    $burn_result = $burn_stmt->get_result();
    if ($burn_result && $row = $burn_result->fetch_assoc()) {
        $burned = (int)$row['session_calorie_burned'];
    }
    $burn_stmt->close();

    $connection->close();

    echo json_encode([
        'success' => true,
        'consumed' => $consumed,
        'burned' => $burned
    ]);
    exit;
}

// If someone tries GET or other method
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
exit;
?>