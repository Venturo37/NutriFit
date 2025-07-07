<?php
include('../features/connection.php');

$connection->query("SET time_zone = '+08:00'");

// Handle form submission via AJAX
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $meal_name = $_POST['meal_name'] ?? '';
    $carbs = (float)($_POST['carbs'] ?? 0);
    $protein = (float)($_POST['protein'] ?? 0);
    $fats = (float)($_POST['fats'] ?? 0);
    $usr_id = $_SESSION['usr_id'] ?? null;

    if (!$usr_id) {
        echo json_encode(["success" => false, "message" => "User not logged in."]);
        exit();
    }

    $total_calories = (float)(($carbs * 3) + ($protein * 4) + ($fats * 9)); 

    // Insert into manual_input_t
    $insert_manual = "INSERT INTO manual_input_t (meal_name, meal_carbohydrates, meal_protein, meal_fats, usr_id)
        VALUES (?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($insert_manual);
    $stmt->bind_param("sdddi", $meal_name, $carbs, $protein, $fats, $usr_id);

    if ($stmt->execute()) {
        $manual_id = $connection->insert_id; 
        $stmt->close();

        if (!$manual_id) {
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to get manual_id after insert: " . $connection->error
                ]);
                exit();
            }

        // Insert into user_meal_intake_t with only manual_id and timestamp
        $insert_intake = "INSERT INTO user_meal_intake_t (manual_id, mlog_timestamp)
            VALUES (?, NOW())";
        $stmt2 = $connection->prepare($insert_intake);
        $stmt2->bind_param("i", $manual_id);

        if ($stmt2->execute()) {
            echo json_encode(["success" => true, "message" => "Meal recorded successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to insert into user_meal_intake_t."]);
        }

        $stmt2->close();
    } else {
        echo json_encode(["success" => false, "message" => "Failed to insert into manual_input_t."]);
    }

    $connection->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../styles/nutrition.css">
<!-- <script src="../javascript/nutrition.js" defer></script> -->

    <?php 
        include('../features/embed.php'); 
    ?>
    <title>Manual Input</title>
</head>
<body>
<div class="popup-overlay">
    <div class="popup-container">
        <span class="close-btn">&times;</span>
        <h2>Calorie Input</h2>

        <!-- The form no longer needs an action, JS will handle it -->
        <form id="calorie-form">
            <div class="form-group">
                <label for="meal-name">Name of the Meal</label>
                <input type="text" id="meal-name" name="meal_name" required>
            </div>

            <div class="calorie-calculator">
                <p>Insert the grams of your meal to calculate calorie intake</p>
                <div class="form-group">
                    <label for="carbs">Carbs (g):</label>
                    <input type="number" id="carbs" name="carbs" value="0" min="0">
                </div>
                <div class="form-group">
                    <label for="protein">Protein (g):</label>
                    <input type="number" id="protein" name="protein" value="0" min="0">
                </div>
                <div class="form-group">
                    <label for="fats">Fats (g):</label>
                    <input type="number" id="fats" name="fats" value="0" min="0">
                </div>
                <p class="total-calories">Total Calories: 0</p>
            </div>

            <button type="submit" class="insert-btn">Insert</button>
        </form>
    </div>
</div>

</body>
</html>