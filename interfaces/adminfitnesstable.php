<?php
// NAME: Ms. CHEAH XUE XIAN
// Project name: adminfitnesstable.php
// DESCRIPTION OF PROGRAM: lists all workouts from the workout_t table. Each workout shows its image and name, and includes an edit icon linking to the workout’s edit form. 
//   The page also provides buttons to manage categories and add a new fitness style.

// FIRST WRITTEN: 2/6/2025
// LAST MODIFIED: 9/7/2025
// Database connection
include('../features/connection.php');
// We need work_id for the edit link
$sql = "SELECT work_id, work_name FROM workout_t";
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
  <title>NutriFit - Fitness Content Management</title>
  <link rel="stylesheet" href="../styles/style1.css" />
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
</head>
<body>
  <?php
include ("../features/header.php")
?>
<div id="content">
  <main>
    
    <h2>Fitness Content Management</h2>
    <div class="actions">
        <a href="../features/managecategory.php" class="btn manage">MANAGE Category</a>
        <a href="../features/addfitness_form.php" class="btn add">ADD Fitness Style</a>
      </div>
    </div>
    <div class="cards">
      <?php while($row = $result->fetch_assoc()): ?>
        <div class="card">
          <!-- Edit Icon re-added here -->
          <a href="../features/editfitness.php?work_id=<?= $row['work_id'] ?>" class="edit-icon">
            <i class="fa-solid fa-pencil"></i>
          </a>
          <!-- Image from database -->
          <img src="../features/getworkoutimage.php?work_id=<?= $row['work_id'] ?>" alt="<?= htmlspecialchars($row['work_name']) ?>">
          <!-- Workout Name -->
          <h3><?= htmlspecialchars($row['work_name']) ?></h3>
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