<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: addfitness_form.php
// DESCRIPTION OF PROGRAM: generates a web page with a form for adding a new fitness exercise. It connects to a database to fetch 
//   available categories for a dropdown menu. It also retrieves any success or error messages from the session to display to the user. 
//   The form collects an image, exercise name, description, category, time estimates for different difficulty levels (beginner, intermediate, intense), 
//   and a MET value. Submitted values are retained if there’s an error to improve the user experience. When the form is submitted, 
//   it sends the data to addfitness.php for processing.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
include('connection.php'); // Assuming this file establishes your database connection ($connect).


// Fetch categories for the dropdown menu
$cat_result = $connection->query("SELECT cate_id, cate_name FROM category_t ORDER BY cate_name");

// --- Retrieve and clear session error/success messages ---
$errorMessage = '';
if (isset($_SESSION['add_fitness_error'])) {
    $errorMessage = $_SESSION['add_fitness_error'];
    unset($_SESSION['add_fitness_error']); // Clear the error message after displaying
}

$successMessage = '';
if (isset($_SESSION['add_fitness_success'])) {
    $successMessage = $_SESSION['add_fitness_success'];
    unset($_SESSION['add_fitness_success']); // Clear the success message
}

// Retain submitted values on error (optional, but good for user experience)
$work_name = isset($_POST['work_name']) ? htmlspecialchars($_POST['work_name']) : '';
$work_description = isset($_POST['work_description']) ? htmlspecialchars($_POST['work_description']) : '';
$cate_id_selected = isset($_POST['cate_id']) ? intval($_POST['cate_id']) : '';
$work_beginner_val = isset($_POST['work_beginner']) ? htmlspecialchars($_POST['work_beginner']) : '';
$work_intermediate_val = isset($_POST['work_intermediate']) ? htmlspecialchars($_POST['work_intermediate']) : '';
$work_intense_val = isset($_POST['work_intense']) ? htmlspecialchars($_POST['work_intense']) : '';
$work_MET_val = isset($_POST['work_MET']) ? htmlspecialchars($_POST['work_MET']) : '';

?>
<?php
include('embed.php')
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add New Fitness</title>
  <link rel="stylesheet" href="../styles/editfitness.css" /> 
</head>
<body>
<?php include("header.php") ?>

<header class="new-fitness-banner">
  <a href="../interfaces/adminfitnesstable.php"><i class="fa-solid fa-arrow-left"></i></a>
  <span>ADD NEW FITNESS</span>
</header>

<main class="form-container">
  <?php if (!empty($errorMessage)): ?>
    <div class="error-message" style="color:red; margin-bottom: 15px; padding: 10px; border: 1px solid red; background-color: #ffe6e6; border-radius: 5px; font-weight: 500;">
      <?= htmlspecialchars($errorMessage) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($successMessage)): ?>
    <div class="success-message" style="color:green; margin-bottom: 15px; padding: 10px; border: 1px solid green; background-color: #e6ffe6; border-radius: 5px; font-weight: 500;">
      <?= htmlspecialchars($successMessage) ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" action="addfitness.php">
    <div class="image-preview">
      <img src="placeholder.png" alt="Upload Image" style="max-width:100%; height:auto;">
    </div>
    <label>Image: * <input type="file" name="image" required></label>

    <label>Fitness Name: * <input type="text" name="work_name" value="<?= $work_name ?>" required></label>
    <label>Descriptions: * <textarea name="work_description" required><?= $work_description ?></textarea></label>

    <div class="form-grid">
      <div class="category-select">
        <select name="cate_id" required>
          <option value="" disabled <?php if(empty($cate_id_selected)) echo 'selected'; ?>>Select Category</option>
          <?php
          // Populate category dropdown options
          if ($cat_result && $cat_result->num_rows > 0) {
              while ($cat_row = $cat_result->fetch_assoc()) {
                  $selected = ($cate_id_selected == $cat_row['cate_id']) ? 'selected' : '';
                  echo "<option value='" . htmlspecialchars($cat_row['cate_id']) . "' $selected>" . htmlspecialchars($cat_row['cate_name']) . "</option>";
              }
          }
          ?>
        </select>
      </div>

      <div class="times">
        <label>Beginner (min):* <input type="number" name="work_beginner" value="<?= $work_beginner_val ?>" required min="0" step="1"></label>
        <label>Intermediate (min):* <input type="number" name="work_intermediate" value="<?= $work_intermediate_val ?>" required min="0" step="1"></label>
        <label>Intense (min):* <input type="number" name="work_intense" value="<?= $work_intense_val ?>" required min="0" step="1"></label>
      </div>

      <label>MET (avg.):* <input type="number" step="0.01" name="work_MET" value="<?= $work_MET_val ?>" required min="0"></label>
    </div>

    <div class="form-actions">
      <button type="submit" class="save-btn">Add Fitness</button>
    </div>
  </form>
</main>

<?php include("footer.php") ?>
</body>
</html>
<?php
// Close the database connection at the end of the script
$connection->close();
?>