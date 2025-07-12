<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: getworkoutimage.php
// DESCRIPTION OF PROGRAM: fetches and serves a workout image from the database using work_id. It sets the correct MIME type for the browser or returns a 404 if the image is not found.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
// Connect to database
include('connection.php');

if (!isset($_GET['work_id'])) {
    http_response_code(400);
    echo "Missing work_id";
    exit;
}

$work_id = intval($_GET['work_id']);
$sql = "SELECT work_image FROM workout_t WHERE work_id = $work_id";
$result = $connection->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $imageData = $row['work_image'];

    // Detect MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($imageData);

    header("Content-Type: $mime");
    echo $imageData;
} else {
    http_response_code(404);
    echo "Image not found.";
}

$connection->close();
?>
