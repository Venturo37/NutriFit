<?php 
    include ('database_connection.php');
    $picSql = "SELECT pic_id FROM profile_picture_t";
    $picResult = mysqli_query($connection, $picSql);

    $workoutSql = "SELECT work_id FROM workout_t";
    $workoutResult = mysqli_query($connection, $workoutSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index</title>
    <style>
        .image-row {
            display: flex;
            flex-wrap: nowrap; /* prevents wrapping to next line */
            overflow-x: auto;   /* allows horizontal scrolling if too many */
            gap: 10px;
        }
        .image-row img {
            height: 150px; /* or any size you want */
            object-fit: cover;
            border: 1px solid;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <h2>Profile Pictures</h2>
<div class="image-row">
    <?php
    while ($row = mysqli_fetch_assoc($picResult)) {
        $id = $row['pic_id'];
        echo "<img src='database_connection.php?id=$id' alt='Image ID $id'>";
    }
    ?>
</div>

<h2>Workout Images</h2>
<div class="image-row">
    <?php
    while ($row = mysqli_fetch_assoc($workoutResult)) {
        $id = $row['work_id'];
        echo "<img src='database_connection.php?id=$id' alt='Workout ID $id'>";
    }
    ?>
</div>
</body>
</html>