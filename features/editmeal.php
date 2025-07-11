<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: editmeal.php
// DESCRIPTION OF PROGRAM: handles editing a meal entry in a database. It displays a form pre-filled with the meal’s current details, 
//   validates and updates any changes (including a new image upload with checks for type and size), logs the update action, and provides an 
//   option to delete the meal with a confirmation modal.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
// Your existing PHP code to fetch data is fine.
// $connection = new mysqli("localhost", "root", "", "nutrifit");
// if ($connection->connect_error) { die("Connection failed: " . $connection->connect_error); }
include('connection.php');
$acting_adm_id = $_SESSION['adm_id'];

// Define a maximum file size (e.g., 5MB)
$max_file_size = 5 * 1024 * 1024; // 5 MB in bytes

// Define allowed MIME types for JPEG and PNG
$allowed_image_types = ['image/jpeg', 'image/jpg', 'image/png']; // Added 'image/png'

// Variable to hold error messages for display
$errorMessage = '';

// Logic to handle the form submission (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meal_id'])) {
    $meal_id = intval($_POST['meal_id']);
    $meal_name = $connection->real_escape_string($_POST['meal_name']);
    $meal_description = $connection->real_escape_string($_POST['meal_description']);
    $mltm_id = intval($_POST['mltm_id']);
    $carbs = floatval($_POST['carbs']);
    $protein = floatval($_POST['protein']);
    $fat = floatval($_POST['fat']);

    $updateImage = false; // Flag to determine if image update is needed

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // --- Start of new image validation logic ---

        // 1. Check file type (MIME type)
        $file_type = mime_content_type($_FILES['image']['tmp_name']); // Use mime_content_type for better accuracy
        if (!in_array($file_type, $allowed_image_types)) {
            $errorMessage = "Error: Only JPEG and PNG images are allowed. Please upload a .jpg, .jpeg, or .png file."; // Updated message
        }
        // 2. Check file size (only if type is okay or if type error is separate)
        else if ($_FILES['image']['size'] > $max_file_size) {
            $errorMessage = "Error: The uploaded image is too large. Please choose an image smaller than " . ($max_file_size / (1024 * 1024)) . "MB.";
        } else {
            // File is within limits and type is correct, prepare for image update
            $imgData = file_get_contents($_FILES['image']['tmp_name']);
            $updateImage = true;
        }
        // --- End of new image validation logic ---

    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other file upload errors (e.g., PHP ini limits, partial upload)
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMessage = "Upload Error: The uploaded file exceeds the server's maximum file size limit (check php.ini).";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = "Upload Error: The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = "Upload Error: The image was only partially uploaded. Please try again.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMessage = "Upload Error: Missing a temporary folder on the server.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessage = "Upload Error: Failed to write image to disk (server permission issue).";
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMessage = "Upload Error: A PHP extension stopped the image upload.";
                break;
            default:
                $errorMessage = "Upload Error: An unknown error occurred during image upload.";
                break;
        }
    }

    // Only proceed with database update if no image-related error occurred ($errorMessage is empty)
    if (empty($errorMessage)) {
        if ($updateImage) {
            $stmt = $connection->prepare("UPDATE meal_t SET meal_name=?, meal_description=?, mltm_id=?, meal_carbohydrates=?, meal_protein=?, meal_fats=?, meal_image=? WHERE meal_id=?");
            $null = NULL; // For binding NULL for the blob type
            $stmt->bind_param("ssiddsbi", $meal_name, $meal_description, $mltm_id, $carbs, $protein, $fat, $null, $meal_id);
            $stmt->send_long_data(6, $imgData); // Index 6 is the 7th parameter (meal_image)
        } else {
            // No new image or existing image retained
            $stmt = $connection->prepare("UPDATE meal_t SET meal_name=?, meal_description=?, mltm_id=?, meal_carbohydrates=?, meal_protein=?, meal_fats=? WHERE meal_id=?");
            $stmt->bind_param("ssiddii", $meal_name, $meal_description, $mltm_id, $carbs, $protein, $fat, $meal_id);
        }

        if ($stmt->execute()) {
            $logStmt = $connection->prepare("INSERT INTO meal_management_t (adm_id, meal_id, meal_mana_action, meal_mana_timestamp) 
                VALUES (?, ?, 'Updated', NOW())");
            $logStmt->bind_param("ii", $acting_adm_id, $meal_id);
            $logStmt->execute();
            $logStmt->close();
            
            header("Location: ../interfaces/adminmealtable.php?updated=1");
            exit;
        } else {
            $errorMessage = "Error updating meal: " . $stmt->error;
        }
    }
    // If errorMessage is not empty here, it means an error occurred, and we won't redirect.
    // The script will continue to display the form with the error message.
}

// Logic to display the form with existing data (or re-display with error)
if (!isset($_GET['meal_id'])) { die("No meal_id specified."); }
$meal_id = intval($_GET['meal_id']);

$stmt = $connection->prepare("SELECT * FROM meal_t WHERE meal_id = ?");
$stmt->bind_param("i", $meal_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) { die("No meal found with that ID."); }
$row = $result->fetch_assoc();
$stmt->close();
$imgSrc = "getmealimage.php?meal_id=" . $row['meal_id'];
$total_kcal = ($row['meal_carbohydrates'] * 4) + ($row['meal_protein'] * 4) + ($row['meal_fats'] * 9);
?>

<?php
include('embed.php')
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Editing: <?= htmlspecialchars($row['meal_name']) ?></title>
  <link rel="stylesheet" href="../styles/editmeal.css">
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
</head>
<?php
include ("header.php")
?>
<body>
  <div class="edit-banner">
    <a href="../interfaces/adminmealtable.php"><i class="fa-solid fa-arrow-left"></i></a>
    <span>EDITING: <?= htmlspecialchars($row['meal_name']) ?></span>
  </div>
  <main class="form-container">
    <?php if (!empty($errorMessage)): ?>
        <div class="error-message" style="color:red; margin-bottom: 15px; padding: 10px; border: 1px solid red; background-color: #ffe6e6; border-radius: 5px;">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="editmeal.php?meal_id=<?= $meal_id ?>">
      <div class="image-preview">
        <img src="<?= $imgSrc ?>" alt="Current Image">
      </div>
      <label>Image:* <input type="file" name="image"></label>
      <label>Meal Name:* <input type="text" name="meal_name" value="<?= htmlspecialchars($row['meal_name']) ?>" required></label>
      <label>Meal Ingredients:* <textarea name="meal_description" required><?= htmlspecialchars($row['meal_description']) ?></textarea></label>
      <label for="meal_time">Meal Time:</label>
      <select name="mltm_id" id="meal_time" required>
        <?php
        $meal_time_result = $connection->query("SELECT mltm_id, mltm_period FROM meal_time_t ORDER BY mltm_id");
        while ($mt_row = $meal_time_result->fetch_assoc()) {
            $selected = ($row['mltm_id'] == $mt_row['mltm_id']) ? 'selected' : '';
            echo "<option value='" . htmlspecialchars($mt_row['mltm_id']) . "' $selected>" . htmlspecialchars($mt_row['mltm_period']) . "</option>";
        }
        ?>
      </select>
      <div class="macros-section">
        <div class="macros-title"><b>Approx Macronutrients</b></div>
        <div class="macros-row">
          <label>Carbs (g):* <input type="number" name="carbs" value="<?= $row['meal_carbohydrates'] ?>" required></label>
          <label>Protein (g):* <input type="number" name="protein" value="<?= $row['meal_protein'] ?>" required></label>
          <label>Fat (g):* <input type="number" name="fat" value="<?= $row['meal_fats'] ?>" required></label>
        </div>
        <div class="macros-kcal">
          <b>Total AVG Kcal:</b>
          <span id="total-kcal"><?= round($total_kcal) ?> kcal</span>
        </div>
      </div>
      <input type="hidden" name="meal_id" value="<?= $row['meal_id'] ?>">
      
      <div class="form-actions">
        <button type="button" class="delete-btn" onclick="showDeleteModal()">Delete</button>
        <button type="submit" class="save-btn">Save Changes</button>
      </div>
    </form>
  </main>

  <form id="deleteForm" method="post" action="deletemeal.php" style="display:none;">
    <input type="hidden" name="meal_id" value="<?= $row['meal_id'] ?>">
  </form>

  <div id="deleteModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
      <p>Are you sure you want to remove this meal?</p>
      <div class="modal-btn-row">
        <button class="delete-btn" onclick="hideDeleteModal()">No</button>
        <button class="save-btn" onclick="confirmDelete()">Yes</button>
      </div>
    </div>
  </div>
<?php
include ("footer.php")
?>
  <script>
    function showDeleteModal() {
      document.getElementById('deleteModal').style.display = 'flex';
    }
    function hideDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
    }
    function confirmDelete() {
      // Submits the hidden delete form
      document.getElementById('deleteForm').submit();
    }
  </script>
</body>
</html>
<?php $connection->close(); ?>