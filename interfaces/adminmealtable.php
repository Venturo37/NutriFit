<?php
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
  <!-- Added Font Awesome for the icons (fire and pencil) -->
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
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
        <a href="../features/mealcategory.php" class="btn manage">MANAGE Category</a>
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