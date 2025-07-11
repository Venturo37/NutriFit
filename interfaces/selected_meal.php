<?php

/*

NAME : Mr. Chan Rui Jie 
PROJECT NAME : selected_meal.php 
DESCRIPTION OF PROGRAM :  
    This is the page that shows all information of a meal that user has selected.
FIRST WRITTEN : June 14th, 2025  
LAST MODIFIED : July 11th, 2025  

*/

    include('../features/connection.php');

    $meal_id = isset($_POST['meal_id']) ? (int)$_POST['meal_id'] : 0;
    $meal = null;

    if ($meal_id > 0) {
        $stmt = $connection->prepare("SELECT * FROM meal_t WHERE meal_id = ?");
        $stmt->bind_param("i", $meal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $meal = $result->fetch_assoc();
        }
        $stmt->close();
    }

    if (!$meal) {
        echo "<h2>Meal not found.</h2>";
        exit;
    }

    // Calculate calories
    $calories = ($meal['meal_carbohydrates'] * 3) + ($meal['meal_protein'] * 4) + ($meal['meal_fats'] * 9);

    // Convert image
    $meal_image = '';
    if (!empty($meal['meal_image'])) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->buffer($meal['meal_image']);
        $meal_image = 'data:' . $mime_type . ';base64,' . base64_encode($meal['meal_image']);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php 
        include('../features/embed.php'); 
    ?>
    <title>NutriFit - Meal</title>
<link rel="stylesheet" href="../styles/nutrition.css">
<!-- <script src="../javascript/nutrition.js" defer></script> -->
</head>
<body>
    <?php include ('../features/header.php'); ?>

    <div id="content" class="selected_meal_page">
    <div class="shape_3"></div>
    <div class="shape_4"></div>
    <div class="meal_container">
        <a href="diet_page_name.php" class="back_arrow"><i class="fas fa-arrow-left"></i></a>
        <div class="meal_details_card">
            <div class="nutrition_info">
                <p>Average Calorie:</p>
                <h2><i class="fa-solid fa-fire-flame-curved"></i><?= round($calories) ?> Kcal</h2>
                <p>Carbs (approx): <?= $meal['meal_carbohydrates'] ?> g</p>
                <p>Protein (approx): <?= $meal['meal_protein'] ?> g</p>
                <p>Fat (approx): <?= $meal['meal_fats'] ?> g</p>
            </div>
            <div class="meal_image_container">
                <img src="<?= $meal_image ?>" alt="<?= htmlspecialchars($meal['meal_name']) ?>" class="meal_photo">
                <h1 class="meal_title"><?= htmlspecialchars($meal['meal_name']) ?></h1>
            </div>
            <div class="person_image_container">
                    <img src="../images/Barbell_woman.png" alt="Person exercising" class="person_photo">
                </div>
            <div class="ingredients_and_action">
                <div class="ingredients_list">
                    <h3>Ingredient</h3>
                    <?php if (!empty($meal['meal_description'])): ?>
                        <p class="meal_description"><?= nl2br(htmlspecialchars($meal['meal_description'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="choose_meal_button_container">
                <button class="choose_meal_btn" data-meal-id="<?= $meal['meal_id'] ?>">Choose Meal</button>
            </div>
        </div>
    </div>
</div>

    <?php include ('../features/footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chooseBtn = document.querySelector('.choose_meal_btn');

            chooseBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const mealId = this.dataset.mealId;

                fetch('../features/insert_selected_meal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ meal_id: mealId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update calorie UI via AJAX after successful insert
                        fetch('../features/get_calorie_summary.php')
                            .then(res => res.json())
                            .then(update => {
                                updateCalories(update.consumed, update.burned);
                                document.getElementById('kcalBurnedText').textContent = update.burned;
                            });

                        // Redirect back to diet page
                        window.location.href = 'diet_page_name.php';
                    } else {
                        alert('Failed to log meal: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error submitting meal:', err);
                });
            });
        });
        </script>

    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
</body>
</html>