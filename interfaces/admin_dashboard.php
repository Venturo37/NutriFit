<?php
// if (session_start() === PHP_SESSION_NONE) {
//     session_start();
// }

include('../features/connection.php');

include('../features/restriction.php');

if (!isset($_SESSION['adm_id'])) {
// User is not logged in, redirect to login page
    header('Location: authentication.php');
    exit();
}
$adm_id = $_SESSION['adm_id'];

function getAdminData ($connection, $adm_id) {
    $admin_query = $connection->prepare(
        "SELECT adm_name, pic_id 
        FROM admin_t 
        WHERE adm_id = ?"
    );
// data type:
// i = integer
// s = string
// d = double
// b = blob
// bind_param safely bind variables to the placeholders (?) in an SQL query
    $admin_query->bind_param("i", $adm_id);
    $admin_query->execute();
    $admin = $admin_query->get_result()->fetch_assoc();
    $admin_query->close();
// $admin = [
//     'adm_name' => 'Admin admin',
//     'pic_id' => 2
// ];
    return $admin;
}


function getProfilePic ($connection, $pic_id) {
    $profile_src = '';
    $profile_statement = $connection->prepare(
        "SELECT pic_picture 
        FROM profile_picture_t 
        WHERE pic_id = ?"
    );
    // $profile_statement->bind_param("i", $user['pic_id']);
    $profile_statement->bind_param("i", $pic_id);
    $profile_statement->execute();
    $profile_blob = null;
    $profile_statement->bind_result($profile_blob);
    // STORES results into $profile_blob
    if ($profile_statement->fetch()) {
        // Identify the Multipurpose Internet Mail Extensions(MIME) type of the profile picture
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->buffer($profile_blob);
        $base64 = base64_encode($profile_blob);
        $profile_src = 'data:' . $mime_type . ';base64,' . $base64;
    } 
    $profile_statement->close();
    return $profile_src;
}


function getFitnessActivityCounts($connection) {
    $data = [];
    $query = "SELECT w.work_name, COUNT(*) as count
        FROM user_workout_session_t uws JOIN workout_t w ON uws.work_id = w.work_id
        GROUP BY w.work_name
        ORDER BY count DESC";
    $query_result = $connection->query($query);

    if (!$query_result) {
        die("Query failed: " . $connection->error);
    }
    while ($row = $query_result->fetch_assoc()) {
        $data[] = $row;
    }
    // [
    //     ["work_name" => "Push Ups", "count" => 5],
    //     ["work_name" => "Running", "count" => 2],
    //     ["work_name" => "Jumping Jacks", "count" => 2],
    //     ["work_name" => "Squats", "count" => 1],
    // ]
    $top3 = array_slice($data,0,3);
    $othersCount = 0;

    if (count($data) > 3) {
        $others = array_slice($data,3);
        foreach ($others as $item) {
            $othersCount += $item['count'];
        }
        $top3[] = ['work_name' => 'Others', 'count' => $othersCount];
    }

    return $top3;
}

function getMealIntakeCounts ($connection) {
    $data = [];
    $query = "SELECT meal_name, SUM(count) as count 
        FROM (
            SELECT m.meal_name, COUNT(*) as count
            FROM user_meal_intake_t umi JOIN meal_t m ON umi.meal_id = m.meal_id
            WHERE umi.meal_id IS NOT NULL
            GROUP BY m.meal_name
            UNION ALL
            SELECT 'Others' AS meal_name, COUNT(*) AS count
            FROM user_meal_intake_t
            WHERE manual_id IS NOT NULL
        ) AS combined
        GROUP BY meal_name
        ORDER BY count DESC";
    $query_result = $connection->query($query);
    
    if (!$query_result) {
        die("Query failed: " . $connection->error);
    }
    while ($row = $query_result->fetch_assoc()) {
        $data[] = $row;
    }
    // [
    //     ["meal_name" => "Others", "count" => 5],
    //     ["meal_name" => "Spaghetti", "count" => 3],
    //     ["meal_name" => "Salad", "count" => 2]
    // ]

    $top3 = array_slice($data,0,3);
    $othersCount = 0;

    if (count($data) > 3) {
        $others = array_slice($data,3);
        foreach ($others as $item) {
            $othersCount += $item["count"];
        }
        $top3[] = ["meal_name"=> "Others", 'count' => $othersCount];
    }
    return $top3;
}

$fitness_data = getFitnessActivityCounts($connection);
usort($fitness_data, function ($a, $b) {
    if ($a['work_name'] === 'Others') {
            return 1;
        }
        if ($b['work_name'] === 'Others') {
            return -1;
        }
        return $b['count']- $a['count'];
});
$meal_data = getMealIntakeCounts($connection);
usort($meal_data, function ($a, $b) {
    if ($a['meal_name'] === 'Others') {
            return 1;
        }
        if ($b['meal_name'] === 'Others') {
            return -1;
        }
        return $b['count']- $a['count'];
});





$user = getAdminData($connection,$adm_id);
$profile_src = getProfilePic ($connection, $user['pic_id']);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <?php include('../features/embed.php'); ?>

</head>
<body>
    <?php include('../features/header.php'); ?>
    <div id="content">
        <div class="account">
            <div class="user_profile">
                <div class="profile_pic">
                    <img src="<?php echo $profile_src ?>" alt="Profile Picture">
                </div>
                <div class="name">
                    <h1><?php echo $user['adm_name'] ?></h1>
                    <p>PROFILE</p>
                </div>
            </div>
            <button class="update_profile_btn">
                Update Profile
            </button>
        </div>
        <div id="update_container" class="update_container" style="display: none;">
            <?php include('update_profile.php');?>
        </div>
        <div class="directory_section">
            <div class="directory_container">
                <button class="fitness_content_management_directory">
                    Fitness Content Management <span><i class="fa-solid fa-chevron-right"></i></span>
                </button>
                <button class="diet_content_management_directory">
                    Diet Content Management <span><i class="fa-solid fa-chevron-right"></i></span>
                </button>
                <button class="user_management_directory">
                    User Management <span><i class="fa-solid fa-chevron-right"></i></span>
                </button>
                <button class="view_user_feedback_directory">
                    View User Feedback <span><i class="fa-solid fa-chevron-right"></i></span>
                </button>
            </div>
        </div>
        <div class="statistics">
            <div class="user_fitness_statistic">
                <h2 class="fitness_statistic_header">
                    Statistics
                    <span>User Fitness Activity</span>
                </h2>

                <div class="statistic_container">
                    <div class="chart_container">
                        <div class="chart_wrapper">
                            <canvas id="fitnessChart"></canvas>
                        </div>
                    </div>

                    <div class="statistic_details">
                        <div class="statistic_summary">
                            <?php
                                $fitnessColors = ['#4A3AFF', '#C6D2FD', '#E0C6FD', '#962DFF'];
                                $total_fitness = array_sum(array_column($fitness_data, 'count'));
                                foreach ($fitness_data as $index => $item) {
                                    // $percentage = $total_fitness ? round(($item['count'] / $total_fitness) * 100) : 0;
                                    $color = $fitnessColors[$index % count($fitnessColors)];
                            ?>
                                <div class="statistic_item">
                                    <div class="statistic_sub_item">
                                        <!-- <div class="statistic_percentage">
                                            <?php 
                                                // echo $percentage 
                                            ?>%
                                        </div> -->
                                        <div class="statistic_dot" style="background-color: <?php echo $color ?>;"></div>
                                        <div class="statistic_name">
                                            <?php echo htmlspecialchars($item['work_name']) ?>
                                        </div>
                                    </div>
                                    <div class="statistic_count">
                                        <?php echo $item['count'] ?>
                                    </div>
                                </div>
                            <?php
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="user_nutrition_statistic">
                <h2 class="nutrition_statistic_header">
                    Statistics
                    <span>User Meal Intake Activity</span>
                </h2>
                
                <div class="statistic_container">
                    <div class="chart_container">
                        <div class="chart_wrapper">
                            <canvas id="mealChart"></canvas>
                        </div>
                    </div>

                    <div class="statistic_details">
                        <div class="statistic_summary">
                            <?php
                                $mealColors = ['#4A3AFF', '#C6D2FD', '#E0C6FD', '#962DFF'];
                                $total_meal = array_sum(array_column($meal_data, 'count'));
                                foreach ($meal_data as $index => $item) {
                                    // $percentage = $total_meal ? round(($item['count'] / $total_meal) * 100) : 0;
                                    $color = $mealColors[$index % count($mealColors)];
                            ?>
                                <div class="statistic_item">
                                    <div class="statistic_sub_item">
                                        <!-- <div class="statistic_percentage">
                                            <?php 
                                            echo $percentage 
                                            ?>%
                                        </div> -->
                                        <div class="statistic_dot" style="background-color: <?php echo $color ?>;"></div>
                                        <div class="statistic_name">
                                            <?php echo htmlspecialchars($item['meal_name']) ?>
                                        </div>
                                    </div>
                                    <div class="statistic_count">
                                        <?php echo $item['count'] ?>
                                    </div>
                                </div>
                            <?php
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>   
        </div>

        <div class="sys_activity_table">
            <h2>User Activity Log</h2>
            <div class="sys_activity_wrapper" id="sys_activity_container">
                <?php include('../features/fetch_sys_activity_table.php') ?>
            </div>
        </div>
    </div>
    <?php include('../features/footer.php'); ?>

    <script>
        window.fitnessData = <?php echo json_encode($fitness_data) ?>;
        window.mealData = <?php echo json_encode($meal_data); ?>;

        function loadActivityLog(page = 1) {
            fetch(`../features/fetch_sys_activity_table.php?page=${page}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById("sys_activity_container").innerHTML = data;
                    document.querySelectorAll(".activity_page_btn").forEach(btn => {
                        btn.addEventListener("click", () => {
                            const nextPage = parseInt(btn.getAttribute("data-page"));
                            if (!isNaN(nextPage)) {
                                loadActivityLog(nextPage)
                            };
                        });
                    });
                });
        }
        
        loadActivityLog();
    </script>
</body>
</html>