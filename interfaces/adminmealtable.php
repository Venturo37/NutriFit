<?php
// NAME: Ms. CHEAH XUE XIAN
// Project name: adminmealtable.php
// DESCRIPTION OF PROGRAM: lists all meals from the meal_t table. For each meal, it calculates total calories based on carbohydrates, protein, and fats, 
//   displays the meal’s image and name, shows its calories with a fire icon, and includes an edit icon linking to the meal edit form. It also provides buttons 
//   to manage meal times and add new meals.

// FIRST WRITTEN: 2/6/2025
// LAST MODIFIED: 9/7/2025
// Database connection
include('../features/connection.php');
// Select all columns needed for the calculation
$sql = "SELECT meal_id, meal_name, meal_carbohydrates, meal_protein, meal_fats FROM meal_t";
$result = $connection->query($sql);
?>
<?php
include('../features/embed.php')
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NutriFit - Diet Content Management</title>
  <link rel="stylesheet" href="../styles/style3.css" />
</head>
<body>
  <?php
include ("../features/header.php")
?>
<div id="content">
  <main>
    <h2>Diet Content Management</h2>
    <div class="actions">
      <!-- <input type="text" placeholder="Search" class="search-box"/> -->
      <div class="actions-right">
        <a href="../features/mealcategory.php" class="btn manage">MANAGE Meal Time</a>
        <a href="../features/addmeal_form.php" class="btn add">ADD New Meals</a>
      </div>
    </div>
    <div class="cards">
      <?php while($row = $result->fetch_assoc()): ?>
        <?php
            // 1. CALCULATE TOTAL KCAL FOR EACH MEAL
            // Use ?? 0 to prevent errors if a value is NULL
            $carbs = $row['meal_carbohydrates'] ?? 0;
            $protein = $row['meal_protein'] ?? 0;
            $fats = $row['meal_fats'] ?? 0; // Using the correct 'meal_fats' column
            
            $total_kcal = ($carbs * 4) + ($protein * 4) + ($fats * 9);
        ?>
        <!-- 2. UPDATED CARD STRUCTURE -->
        <div class="card">
          <a href="../features/editmeal.php?meal_id=<?= $row['meal_id'] ?>" class="edit-icon">
            <i class="fa-solid fa-pencil"></i>
          </a>
          <img src="../features/getmealimage.php?meal_id=<?= $row['meal_id'] ?>" alt="<?= htmlspecialchars($row['meal_name']) ?>">
          <h3><?= htmlspecialchars($row['meal_name']) ?></h3>
          <div class="card-info">
              <i class="fa-solid fa-fire"></i>
              <span><?= round($total_kcal) ?> Kcal</span>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </main>
</div>
<?php
include ("../features/footer.php")
?>
</body>
</html>
<?php $connection->close(); ?>