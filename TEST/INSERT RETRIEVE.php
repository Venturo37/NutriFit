<?php
    include ('connection.php');

// WHEN UPDATING BLOB IMAGE
    // $imagePath = "TEST\NutriFit_Profile\Push Up.png";
    // $imageData = file_get_contents($imagePath);

    // $statement = $connection->prepare("UPDATE workout_t SET work_image = ? WHERE work_id = 1");
    // $null = null;
    // $statement->bind_param("b", $null);
    // $statement->send_long_data(0, $imageData);
    // // you bind a NULL placeholder first and then use send_long_data() to actually send the binary data. IN CASE THE IMAGE IS A LARGE FILE

    // if ($statement->execute()) {
    //     echo "Image updated successfully.";
    // } else {
    //     echo "Error updating image: " . $statement->error;
    // }
    // $statement->close();
    // $connection->close();


// WHEN INSERTING BLOB IMAGE
    // $imagePath = "TEST\NutriFit_Profile\Push Up.png";
    // $imageData = file_get_contents($imagePath);

    

    // $work_name = "Squat";
    // $work_description = "Squats are a fundamental lower body exercise that targets your quads, hamstrings, and glutes.";
    // $cate_id = 2; // Assuming category ID 2 for Lower Body
    // $work_beginner = 30; // Assuming beginner level
    // $work_intermediate = 60; // Assuming intermediate level
    // $work_intense = 90; // Assuming intense level
    // $work_MET = 6.0; // Assuming MET value for squats

    // $imagePath = "TEST\NutriFit_Profile\Squat.webp";
    // $imageData = file_get_contents($imagePath);

    // $statement = $connection->prepare(
    //     "INSERT INTO workout_t (work_name, work_description, cate_id, work_beginner, work_intermediate, work_intense, work_MET, work_image) 
    //     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    // );
    // $null = null; // Placeholder for BLOB data
    // $statement->bind_param(
    //     "ssiddddb", 
    //     $work_name, 
    //     $work_description, 
    //     $cate_id, 
    //     $work_beginner, 
    //     $work_intermediate, 
    //     $work_intense, 
    //     $work_MET, 
    //     $null
    // );
// 'i' → Integer
// 'd' → Double (which covers both float and double in PHP)
// 's' → String
// 'b' → Blob (used for binary data)
// $statement->send_long_data(7, $imageData); // Send the BLOB data
// WHY 7? 
// BECAUSE
// [0] $work_name, 
// [1] $work_description, 
// [2] $cate_id, 
// [3] $work_beginner, 
// [4] $work_intermediate, 
// [5] $work_intense, 
// [6] $work_MET, 
// [7] $null

    // if ($statement->execute()) {
    //     echo "Image inserted successfully.";
    // } else {
    //     echo "Error inserting image: " . $statement->error;
    // }
    // $statement->close();



// RETRIEVE ALL WORKOUTS FROM table
$workout_pic = '';
$workout_statement = $connection->prepare(
    "SELECT work_name, work_image FROM workout_t" // Assuming you want to fetch the image for workout ID 1
);
$workout_statement->execute();
$workout_statement->bind_result($work_name, $workout_img);

$finfo = new finfo(FILEINFO_MIME_TYPE);// Identify the Multipurpose Internet Mail Extensions(MIME) type of the workout image
$workout_cards_html = '<div class="container">';
while ($workout_statement->fetch()) {    
    $mime_type = $finfo->buffer($workout_img);
    $base64 = base64_encode($workout_img);
    $workout_pic = 'data:' . $mime_type . ';base64,' . $base64;

    $workout_cards_html .= '<div class="workout-card">';
    // $workout_cards_html (<div class="container">) . <div class="workout-card">       CONTINUES THE html
    $workout_cards_html .= '<img src="' . $workout_pic . '" alt="' . htmlspecialchars($work_name) . '">';
    $workout_cards_html .= '<h2>' . htmlspecialchars($work_name) . '</h2>';
    $workout_cards_html .= '</div>';
} 
$workout_cards_html .= '</div>';


echo 'MIME type: ' . $mime_type . '<br>';
echo 'Base64: ' . substr($base64, 0, 50) . '...<br>';
$workout_statement->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="content">
        <?php
            echo $workout_cards_html; // Display the workout cards
        ?>
    </div>
</body>
</html>



