<?php
include('connection.php'); 

$acting_adm_id = $_SESSION['adm_id'];

// Establish database connection
// $connection = new mysqli("localhost", "root", "", "nutrifit");
// if ($connection->connect_error) {
//     die("Connection failed: " . $connection->connect_error);
// }

// Define a maximum file size (e.g., 5MB)
$max_file_size = 5 * 1024 * 1024; // 5 MB in bytes

// Define allowed MIME types for JPEG and PNG
$allowed_image_types = ['image/jpeg', 'image/jpg', 'image/png'];

// Variable to hold error messages for display in the HTML
$errorMessage = '';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve Data from Form
    $meal_name = $_POST['meal_name'];
    $meal_description = $_POST['meal_description'];
    $carbs = $_POST['carbs'];
    $protein = $_POST['protein'];
    $fat = $_POST['fat'];
    $mltm_id = $_POST['mltm_id'];

    // Handle Image Upload with validation
    $meal_image = null; // Initialize image data to null

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // 1. Check file type (MIME type)
        $file_type = mime_content_type($_FILES['image']['tmp_name']); // Use mime_content_type for better accuracy
        if (!in_array($file_type, $allowed_image_types)) {
            $errorMessage = "Error: Only JPEG and PNG images are allowed. Please upload a .jpg, .jpeg, or .png file.";
        }
        // 2. Check file size
        else if ($_FILES['image']['size'] > $max_file_size) {
            $errorMessage = "Error: The uploaded image is too large. Please choose an image smaller than " . ($max_file_size / (1024 * 1024)) . "MB.";
        } else {
            // File is valid, read its content
            $meal_image = file_get_contents($_FILES['image']['tmp_name']);
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other file upload errors (e.g., PHP ini limits, partial upload)
        switch ($_FILES['image']['error']) {
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
    } else {
        // This means UPLOAD_ERR_NO_FILE, implying no file was selected or required but not provided.
        $errorMessage = "Error: An image is required for the meal.";
    }

    // Only proceed with database insertion if no validation errors occurred
    if (empty($errorMessage)) {
        // Prepare and Execute SQL Statement
        $stmt = $connection->prepare("INSERT INTO meal_t (meal_name, meal_description, meal_carbohydrates, meal_protein, meal_fats, mltm_id, meal_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        // Bind parameters, using 'b' for blob
        $null = NULL; // Placeholder for the blob data
        $stmt->bind_param("ssdddib", $meal_name, $meal_description, $carbs, $protein, $fat, $mltm_id, $null);
        
        // Send the actual image data for the 7th parameter (index 6)
        $stmt->send_long_data(6, $meal_image);
        
        // Execute and Redirect
        if ($stmt->execute()) {
            $newMealId = $stmt->insert_id; // Get the ID of the newly inserted meal
            $stmt->close();

            $logStmt = $connection->prepare("INSERT INTO meal_management_t (adm_id, meal_id, meal_mana_action, meal_mana_timestamp) 
                VALUES (?, ?, 'Added', NOW())");
            if (!$logStmt) {
                throw new Exception("Database Prepare Error: " . $connection->error);
            }
            $logStmt->bind_param("ii", $acting_adm_id, $newMealId);
            if (!$logStmt->execute()) {
                throw new Exception("Error logging workout addition: " . $logStmt->error);
            }
            $logStmt->close();
            
            // Success: Redirect to adminmealtable.php with a success message (optional)
            header("Location: ../interfaces/adminmealtable.php?added=1");
            exit();
        } else {
            // Database error
            $errorMessage = "Database Error: " . htmlspecialchars($stmt->error);
            $stmt->close();
        }

    }
    // If $errorMessage is not empty at this point, the script will fall through to HTML and display it.
}

// Fetch meal time periods for the dropdown (always needed for the form)
$meal_time_result = $connection->query("SELECT mltm_id, mltm_period FROM meal_time_t ORDER BY mltm_id");

// Close connection at the end of the script after all database operations
$connection->close();
?>

    <script>
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

            // Initial calculation if values are pre-filled (e.g., after an error)
            calculateKcal();
        });
    </script>
</body>
</html>