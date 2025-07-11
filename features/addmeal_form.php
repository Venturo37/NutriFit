<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: addmeal_form.php
// DESCRIPTION OF PROGRAM: to add a new meal. It lets the user upload an image, enter the meal name and ingredients, choose a meal time from the database, 
//     and input macronutrient values (carbs, protein, fat). A small JavaScript calculates and displays the total approximate calories in real time. 
//     The form data is submitted via POST to addmeal.php.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
include ("connection.php");
include ("embed.php")
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>New Meal</title>
  <link rel="stylesheet" href="../styles/addmeal.css">
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
</head>
<body>
   <?php
include ("header.php")
?>
  <header class="page-header">
    <a href="../interfaces/adminmealtable.php"><i class="fa-solid fa-arrow-left"></i></a>
    <span>NEW MEAL</span>
  </header>
  <main class="form-container">
    <form method="POST" enctype="multipart/form-data" action="addmeal.php">
      <div class="image-preview">
        <span>Image</span>
      </div>
<div class="form-group-inline">
    <label for="image-upload">Image:*</label>
    <label for="image-upload" class="file-input-button">Choose File</label>
    <input type="file" id="image-upload" name="image" required style="display:none;">
    <span id="fileNameDisplay">No file chosen</span> </div>
      
      <div class="form-group">
          <label for="meal-name">Meal Name:*</label>
          <input type="text" id="meal-name" name="meal_name" required>
      </div>

      <div class="form-group">
          <label for="meal-ingredients">Meal Ingredients:*</label>
          <textarea id="meal-ingredients" name="meal_description" placeholder="Ingredients" required></textarea>
      </div>

      <div class="form-group-inline">
          <label for="meal-time">Meal Time:</label>
          <select name="mltm_id" id="meal-time" required>
              <option value="" disabled selected>Meal Time</option>
              <?php
              $connection = new mysqli("localhost", "root", "", "nutrifit");
              if (!$connection->connect_error) {
                  $result = $connection->query("SELECT mltm_id, mltm_period FROM meal_time_t ORDER BY mltm_id");
                  if ($result->num_rows > 0) {
                      while($row = $result->fetch_assoc()) {
                          echo "<option value='" . htmlspecialchars($row['mltm_id']) . "'>" . htmlspecialchars($row['mltm_period']) . "</option>";
                      }
                  }
                  $connection->close();
              }
              ?>
          </select>
      </div>

      <div class="macros-section">
          <div class="macros-title">Approx Macronutrients</div>
          <div class="macros-row">
              <div class="macro-field">
                  <label for="carbs">Carbs (g):*</label>
                  <input type="number" id="carbs" name="carbs" required>
              </div>
              <div class="macro-field">
                  <label for="protein">Protein (g):*</label>
                  <input type="number" id="protein" name="protein" required>
              </div>
              <div class="macro-field">
                  <label for="fat">Fat (g):*</label>
                  <input type="number" id="fat" name="fat" required>
              </div>
          </div>
          <div class="total-kcal">
              Total AVG Kcal: <span id="total-kcal">00 kcal</span>
          </div>
      </div>

      <div class="form-actions">
          <button type="submit" class="save-btn">Save Changes</button>
      </div>
    </form>
  </main>
  <?php
include ("footer.php")
?>
    <script>
        document.getElementById('image-upload').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            document.getElementById('fileNameDisplay').textContent = fileName;
        });
        document.addEventListener('DOMContentLoaded', function() {
            const carbsInput = document.querySelector('input[name="carbs"]');
            const proteinInput = document.querySelector('input[name="protein"]');
            const fatInput = document.querySelector('input[name="fat"]');
            const totalKcalSpan = document.getElementById('total-kcal');

            function calculateKcal() {
                const carbs = parseFloat(carbsInput.value) || 0;
                const protein = parseFloat(proteinInput.value) || 0;
                const fat = parseFloat(fatInput.value) || 0;
                const total = (carbs * 4) + (protein * 4) + (fat * 9);
                totalKcalSpan.textContent = Math.round(total) + ' kcal';
            }

            carbsInput.addEventListener('input', calculateKcal);
            proteinInput.addEventListener('input', calculateKcal);
            fatInput.addEventListener('input', calculateKcal);

            calculateKcal();
        });
    </script>
</body>
</html>