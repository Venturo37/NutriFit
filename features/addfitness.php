<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: addfitness.php
// DESCRIPTION OF PROGRAM: generates a web page with a form for adding a new fitness exercise. It connects to a database to fetch 
//   available categories for a dropdown menu. It also retrieves any success or error messages from the session to display to the user. 
//   The form collects an image, exercise name, description, category, time estimates for different difficulty levels (beginner, intermediate, intense), 
//   and a MET value. Submitted values are retained if there’s an error to improve the user experience. When the form is submitted, 
//   it sends the data to addfitness.php for processing.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
include('connection.php'); // Assuming this file establishes your database connection ($connect).

$acting_adm_id = $_SESSION['adm_id'];
// if (!$acting_adm_id) {
//     die("Admin ID not found in session. Cannot log who added the workout.");
// }

// Enable error reporting for debugging. IMPORTANT: Disable in production for security.
error_reporting(E_ALL);
ini_set('display_errors', 1);



// --- Function to redirect with an error message ---
function redirectToFormWithError($message, $connection) {
    $_SESSION['add_fitness_error'] = $message;
    if ($connection && $connection->ping()) { // Check if connection is still open before closing
        $connection->close();
    }
    header("Location: addfitness_form.php"); // Redirect back to the form page
    exit();
}

// Check if the form was submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. Database Connection ---
    $connection = new mysqli("localhost", "root", "", "nutrifit");
    if ($connection->connect_error) {
        // If connection fails, directly die as we can't store error in session
        die("Connection failed: " . $connection->connect_error);
    }

    // Define a maximum file size for image uploads (5MB)
    $max_file_size = 5 * 1024 * 1024; // 5 MB in bytes

    // Define allowed MIME types for JPEG and PNG images
    $allowed_image_types = ['image/jpeg', 'image/jpg', 'image/png'];

    // --- 2. Get Data From Form and Sanitize/Type Cast ---
    // Using real_escape_string for string inputs to prevent SQL injection
    $work_name = $connection->real_escape_string($_POST['work_name']);
    $work_description = $connection->real_escape_string($_POST['work_description']);
    $cate_id = intval($_POST['cate_id']);
    // Casting to intval for minutes as they are typically whole numbers
    $work_beginner = intval($_POST['work_beginner']);
    $work_intermediate = intval($_POST['work_intermediate']);
    $work_intense = intval($_POST['work_intense']);
    $work_MET = floatval($_POST['work_MET']); // MET can be a decimal

    // --- 3. Handle Image Upload with comprehensive validation ---
    $work_image_data = null; // Variable to store the binary image data

    // Check if an image file was selected and uploaded without initial PHP errors
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileSize = $_FILES['image']['size'];
        $fileMimeType = mime_content_type($fileTmpPath);

        // Validate image file type
        if (!in_array($fileMimeType, $allowed_image_types)) {
            redirectToFormWithError("Error: Only JPEG and PNG images are allowed. Please upload a .jpg, .jpeg, or .png file.", $connection);
        }
        // Validate image file size
        else if ($fileSize > $max_file_size) {
            redirectToFormWithError("Error: The uploaded image is too large. Please choose an image smaller than " . ($max_file_size / (1024 * 1024)) . "MB.", $connect);
        } else {
            // Read the binary content of the uploaded image file
            $work_image_data = file_get_contents($fileTmpPath);
            if ($work_image_data === false) {
                redirectToFormWithError("Error: Could not read uploaded image file from temporary location.", $connection);
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other specific PHP file upload errors (excluding UPLOAD_ERR_NO_FILE, which means no file was chosen)
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                redirectToFormWithError("Upload Error: The uploaded file exceeds the server's 'upload_max_filesize' limit in php.ini.", $connection);
                break;
            case UPLOAD_ERR_FORM_SIZE:
                redirectToFormWithError("Upload Error: The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.", $connection);
                break;
            case UPLOAD_ERR_PARTIAL:
                redirectToFormWithError("Upload Error: The image was only partially uploaded. Please try again.", $connection);
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                redirectToFormWithError("Upload Error: Missing a temporary folder on the server.", $connection);
                break;
            case UPLOAD_ERR_CANT_WRITE:
                redirectToFormWithError("Upload Error: Failed to write image to disk (server permission issue).", $connection);
                break;
            case UPLOAD_ERR_EXTENSION:
                redirectToFormWithError("Upload Error: A PHP extension stopped the image upload.", $connection);
                break;
            default:
                redirectToFormWithError("Upload Error: An unknown error occurred during image upload.", $connect);
                break;
        }
    } else {
        // This condition means no file was selected, which is typically required for adding a new item.
        redirectToFormWithError("Error: No image file selected. Please choose an image to upload.", $connect);
    }

    // --- 4. Prepare and Execute SQL INSERT Statement ---
    // If we reach here, all validations have passed
    $sql = "INSERT INTO workout_t (work_name, work_description, work_beginner, work_intermediate, work_intense, work_MET, cate_id, work_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        redirectToFormWithError("Database Prepare Error: " . $connection->error, $connection);
    } else {
        // Bind parameters: 's'=string, 'i'=integer, 'd'=double, 'b'=blob
        // Order: name, desc, beginner, intermediate, intense, MET, cate_id, image
        $null = NULL; // Placeholder for BLOB
        $stmt->bind_param("ssiiidib", $work_name, $work_description, $work_beginner, $work_intermediate, $work_intense, $work_MET, $cate_id, $null);

        // Send the actual binary image data for the 8th parameter (index 7)
        $stmt->send_long_data(7, $work_image_data);

        // --- 5. Execute and Redirect ---
        if ($stmt->execute()) {
            $_SESSION['add_fitness_success'] = "Fitness style added successfully!"; // Optional success message

            $newWorkoutId = $stmt->insert_id; // Get the ID of the newly inserted workout
            $stmt->close();

            $logStmt = $connection->prepare("INSERT INTO workout_management_t (adm_id, work_id, work_mana_action, work_mana_timestamp) 
                VALUES (?, ?, 'Added', NOW())");
            if (!$logStmt) {
                throw new Exception("Database Prepare Error: " . $connection->error);
            }
            $logStmt->bind_param("ii", $acting_adm_id, $newWorkoutId);
            if (!$logStmt->execute()) {
                throw new Exception("Error logging workout addition: " . $logStmt->error);
            }
            $logStmt->close();

            $connection->close();
            header("Location: ../interfaces/adminfitnesstable.php"); // Redirect to the fitness table on success
            exit();
        } else {
            redirectToFormWithError("Error adding fitness style: " . $stmt->error, $connection);
        }
    }

} else {
    // If the request method is not POST (e.g., direct access),
    // redirect to the form page. This script should only be accessed via POST.
    header("Location: addfitness_form.php");
    exit();
}
?>