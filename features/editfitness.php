<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: editfitness.php
// DESCRIPTION OF PROGRAM: displays an edit form for a workout (workout_t) identified by work_id. It allows admins to update workout details, 
//   optionally upload a new image, and handles image validation. Upon saving, it logs the update in workout_management_t. The page also includes a modal 
//   for deleting the workout via deletefitness.php.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
include('connection.php');

$acting_adm_id = $_SESSION['adm_id'];

// Enable error reporting for debugging. IMPORTANT: Disable in production.
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Define a maximum file size for image uploads (5MB)
$maxSize = 5 * 1024 * 1024; // 5 MB in bytes

// Variable to hold error messages, to be displayed in the HTML if not empty
$errorMessage = '';

// Determine work_id for both initial page load (GET) and form submission (POST)
$work_id = 0;
if (isset($_GET['work_id'])) {
    $work_id = intval($_GET['work_id']);
} elseif (isset($_POST['work_id'])) { // Check POST for work_id when form is submitted
    $work_id = intval($_POST['work_id']);
} else {
    // If no work_id is provided via GET or POST, terminate script.
    die("No workout ID specified. Please access this page via an edit link.");
}

// --- Section to handle form submission (HTTP POST request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and convert data received from the form
    $work_name = $connection->real_escape_string($_POST['work_name']);
    $work_description = $connection->real_escape_string($_POST['work_description']);
    $cate_id = intval($_POST['cate_id']);
    $beginner = intval($_POST['work_beginner']);       // Converted to integer
    $intermediate = intval($_POST['work_intermediate']); // Converted to integer
    $intense = intval($_POST['work_intense']);         // Converted to integer
    $met = floatval($_POST['work_MET']);             // Converted to float

    $updateImage = false; // Flag to indicate if a new image was uploaded and needs updating
    $imgData = null;      // Variable to hold the binary content of the new image

    // Check if a new image file was uploaded successfully
    if (isset($_FILES['work_image']) && $_FILES['work_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['work_image']['tmp_name'];
        $fileSize = $_FILES['work_image']['size'];

        // Validate image file size
        if ($fileSize > $maxSize) {
            $errorMessage = "Error: The uploaded image is too large. Please choose an image smaller than " . ($maxSize / (1024 * 1024)) . "MB.";
        } else {
            // Read the binary content of the uploaded image
            $imgData = file_get_contents($fileTmp);
            if ($imgData === false) {
                $errorMessage = "Error: Could not read uploaded image file from temporary location.";
            } else {
                $updateImage = true; // Set flag to true as image data is valid
            }
        }
    } elseif (isset($_FILES['work_image']) && $_FILES['work_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other possible PHP file upload errors (excluding no file chosen)
        switch ($_FILES['work_image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMessage = "Upload Error: The uploaded file exceeds the server's maximum file size limit (check php.ini).";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = "Upload Error: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
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

    // Proceed with database update only if no validation errors occurred
    if (empty($errorMessage)) {
        if ($updateImage) {
            // Prepare SQL UPDATE statement including the image
            $stmt = $connection->prepare("UPDATE workout_t SET work_name=?, work_description=?, cate_id=?, work_beginner=?, work_intermediate=?, work_intense=?, work_MET=?, work_image=? WHERE work_id=?");
            if (!$stmt) {
                $errorMessage = "Database Prepare Error (with image): " . $connection->error;
            } else {                               
                $null = NULL; // Used for binding the BLOB data initially
                // Bind parameters: 's'=string, 'i'=integer, 'd'=double, 'b'=blob
                // The order of types must match the order of columns in the SET clause
                $stmt->bind_param("ssiiiidbi", $work_name, $work_description, $cate_id, $beginner, $intermediate, $intense, $met, $null, $work_id);
                // Send the actual binary image data for the 8th parameter (index 7, as parameters are 0-indexed)
                $stmt->send_long_data(7, $imgData);

                // Execute the update query
                if ($stmt->execute()) {
                  $logStmt = $connection->prepare("INSERT INTO workout_management_t (adm_id, work_id, work_mana_action, work_mana_timestamp) 
                    VALUES (?, ?, 'Updated', NOW())");
                  $logStmt->bind_param("ii", $acting_adm_id, $work_id);
                  $logStmt->execute();
                  $logStmt->close();

                    header("Location: ../interfaces/adminfitnesstable.php?updated=1"); // Redirect on successful update
                    exit; // Terminate script after redirect
                } else {
                    $errorMessage = "Error updating workout (with image): " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            // Prepare SQL UPDATE statement WITHOUT changing the image
            $stmt = $connection->prepare("UPDATE workout_t SET work_name=?, work_description=?, cate_id=?, work_beginner=?, work_intermediate=?, work_intense=?, work_MET=? WHERE work_id=?");
            if (!$stmt) {
                $errorMessage = "Database Prepare Error (without image): " . $connection->error;
            } else {
                // Bind parameters: 's'=string, 'i'=integer, 'd'=double
                $stmt->bind_param("ssiddddi", $work_name, $work_description, $cate_id, $beginner, $intermediate, $intense, $met, $work_id);

                // Execute the update query
                if ($stmt->execute()) {
                  $logStmt = $connection->prepare("INSERT INTO workout_management_t (adm_id, work_id, work_mana_action, work_mana_timestamp) 
                    VALUES (?, ?, 'Updated', NOW())");
                  $logStmt->bind_param("ii", $acting_adm_id, $work_id);
                  $logStmt->execute();
                  $logStmt->close();

                    header("Location: ../interfaces/adminfitnesstable.php?updated=1"); // Redirect on successful update
                    exit; // Terminate script after redirect
                } else {
                    $errorMessage = "Error updating workout (without image): " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    // If errorMessage is not empty after POST, the script will continue to the HTML section
    // to display the form along with the error message.
}

// --- Section to fetch data for displaying the form (runs on GET or after POST with error) ---
// Ensure work_id is valid before fetching data
if ($work_id === 0) {
    die("Invalid workout ID specified for data retrieval.");
}

$stmt = $connection->prepare("SELECT * FROM workout_t WHERE work_id = ?");
$stmt->bind_param("i", $work_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("No workout found with that ID.");
}
$row = $result->fetch_assoc();
$stmt->close();

// Prepare the image source for display in the HTML (base64 encode BLOB data)
// Assuming image data is always present. You might want a placeholder if it could be empty.
$imageSrc = "data:image/jpeg;base64," . base64_encode($row['work_image']);

// Fetch categories for the dropdown menu
$cat_result = $connection->query("SELECT cate_id, cate_name FROM category_t ORDER BY cate_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Editing: <?= htmlspecialchars($row['work_name']) ?></title>
  <link rel="stylesheet" href="../styles/editfitness.css" />
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
</head>
<body>
<?php include("header.php") ?>

<header class="new-fitness-banner">
  <a href="../interfaces/adminfitnesstable.php"><i class="fa-solid fa-arrow-left"></i></a>
  <span>EDITING: <?= htmlspecialchars($row['work_name']) ?></span>
</header>

<main class="form-container">
  <?php if (!empty($errorMessage)): ?>
    <div class="error-message" style="color:red; margin-bottom: 15px; padding: 10px; border: 1px solid red; background-color: #ffe6e6; border-radius: 5px; font-weight: 500;">
      <?= htmlspecialchars($errorMessage) ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" action="editfitness.php?work_id=<?= htmlspecialchars($work_id) ?>">
    <div class="image-preview">
      <img src="<?= $imageSrc ?>" alt="Current Image">
    </div>
    <label>Image: <input type="file" name="work_image"></label>

    <label>Fitness Name: * <input type="text" name="work_name" value="<?= htmlspecialchars($row['work_name']) ?>" required></label>
    <label>Descriptions: * <textarea name="work_description" required><?= htmlspecialchars($row['work_description']) ?></textarea></label>

    <div class="form-grid">
      <div class="category-select">
        <select name="cate_id" required>
          <option disabled>Category</option>
          <?php
          // Populate category dropdown options
          if ($cat_result && $cat_result->num_rows > 0) {
              $cat_result->data_seek(0); // Ensure pointer is at the beginning for fetching
              while ($cat_row = $cat_result->fetch_assoc()) {
                  $selected = ($row['cate_id'] == $cat_row['cate_id']) ? 'selected' : '';
                  echo "<option value='" . htmlspecialchars($cat_row['cate_id']) . "' $selected>" . htmlspecialchars($cat_row['cate_name']) . "</option>";
              }
          }
          ?>
        </select>
      </div>

      <div class="times">
        <label>Beginner (min):* <input type="number" name="work_beginner" value="<?= htmlspecialchars($row['work_beginner']) ?>" required step="1"></label>
        <label>Intermediate (min):* <input type="number" name="work_intermediate" value="<?= htmlspecialchars($row['work_intermediate']) ?>" required step="1"></label>
        <label>Intense (min):* <input type="number" name="work_intense" value="<?= htmlspecialchars($row['work_intense']) ?>" required step="1"></label>
      </div>

      <label>MET (avg.):* <input type="number" step="0.01" name="work_MET" value="<?= htmlspecialchars($row['work_MET']) ?>" required></label>
    </div>

    <input type="hidden" name="work_id" value="<?= htmlspecialchars($row['work_id']) ?>">

    <div class="form-actions">
      <button type="button" class="delete-btn" onclick="showDeleteModal()">Delete</button>
      <button type="submit" class="save-btn">Save Changes</button>
    </div>
  </form>
</main>

<form id="deleteForm" method="post" action="deletefitness.php" style="display:none;">
  <input type="hidden" name="work_id" value="<?= htmlspecialchars($row['work_id']) ?>">
</form>

<div id="deleteModal" class="modal-overlay" style="display:none;">
  <div class="modal-box">
    <p>Are you sure you want to remove this workout?</p>
    <div class="modal-btn-row">
      <button class="delete-btn" onclick="hideDeleteModal()">No</button>
      <button class="save-btn" onclick="confirmDelete()">Yes</button>
    </div>
  </div>
</div>

<script>
  function showDeleteModal() {
    document.getElementById('deleteModal').style.display = 'flex';
  }

  function hideDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
  }

  function confirmDelete() {
    document.getElementById('deleteForm').submit();
  }
</script>

<?php include("footer.php") ?>
</body>
</html>
<?php
// Close the database connection at the end of the script
$connection->close();
?>