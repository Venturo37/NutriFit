<?php
// getmealimage.php

// Use the same connection file as your main page
// Adjust the path if your connection.php file is in a different directory
include('connection.php');

// Check if a meal_id was provided in the URL
if (!isset($_GET['meal_id'])) {
    // If no ID, it's a bad request
    http_response_code(400);
    echo "Error: Missing meal_id parameter.";
    exit;
}

// Get the meal ID from the URL and make sure it's an integer to prevent SQL injection
$meal_id = intval($_GET['meal_id']);

// Prepare and execute the SQL query to get the image data for the specific meal
$sql = "SELECT meal_image FROM meal_t WHERE meal_id = $meal_id";
$result = $connection->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $imageData = $row['meal_image'];

    // Check if the image data is not empty
    if (!empty($imageData)) {
        // This is a crucial step: tell the browser what kind of image it is (e.g., JPEG, PNG)
        // The finfo extension is required for this to work.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->buffer($imageData);

        // Set the content type header to the detected mime type
        header("Content-Type: " . $mime_type);

        // Output the raw image data
        echo $imageData;
    } else {
        // If the image column is empty, show a placeholder
        // Make sure you have a 'placeholder.png' image in your project folder
        header("Content-Type: image/png");
        readfile("path/to/your/placeholder.png"); // <--- IMPORTANT: Update this path
    }

} else {
    // If no meal with that ID was found, show the placeholder image
    header("Content-Type: image/png");
    readfile("path/to/your/placeholder.png"); // <--- IMPORTANT: Update this path
}

$connection->close();
?>