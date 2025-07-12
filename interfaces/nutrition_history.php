<?php
// NAME: CHOW YAN PING
// Project name: Nutrifit
// DESCRIPTION OF PROGRAM: Displays a logged-in user's nutrition intake history, including meals from the system and manual inputs.
// FIRST WRITTEN: 2/6/2025
// LAST MODIFIED: 9/7/2025 
include('../features/connection.php');

include('../features/restriction.php');
$usr_id = $_SESSION['usr_id'];


include('../features/header.php');

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Unified query
$query = "
    SELECT umi.mlog_timestamp AS timestamp,
           COALESCE(m.meal_name, mi.meal_name) AS dish_name,
           COALESCE(m.meal_carbohydrates, mi.meal_carbohydrates) AS carbs,
           COALESCE(m.meal_protein, mi.meal_protein) AS protein,
           COALESCE(m.meal_fats, mi.meal_fats) AS fats
    FROM user_meal_intake_t umi
    LEFT JOIN meal_t m ON umi.meal_id = m.meal_id
    LEFT JOIN manual_input_t mi ON umi.manual_id = mi.manual_id
    WHERE (umi.usr_id = $usr_id OR mi.usr_id = $usr_id)
    ORDER BY timestamp DESC
    LIMIT $limit OFFSET $offset
";

$result = mysqli_query($connection, $query);

// Count total records
$countQuery = "
    SELECT COUNT(*) AS total
    FROM user_meal_intake_t umi
    LEFT JOIN meal_t m ON umi.meal_id = m.meal_id
    LEFT JOIN manual_input_t mi ON umi.manual_id = mi.manual_id
    WHERE (umi.usr_id = $usr_id OR mi.usr_id = $usr_id)
";

$countResult = mysqli_query($connection, $countQuery);
$totalRows = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRows / $limit);
?>

<div class="record_header">
    <h3><i class="fas fa-circle-arrow-left" onclick="window.location.href='user_profile.php'"></i> Nutrition Intake History</h3>
</div>

<div id="content">
    <div class="record_table_wrapper nutrition_history">
        <table class="record_table">
            <thead>
                <tr>
                    <th>Date&Time</th>
                    <th>Dish Name</th>
                    <th>Kcal Consumed</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= date('d/m/y H:i', strtotime($row['timestamp'])) ?></td>
                            <td><?= htmlspecialchars($row['dish_name']) ?></td>
                            <td>
                                <?php
                                $carbs = floatval($row['carbs']);
                                $protein = floatval($row['protein']);
                                $fats = floatval($row['fats']);
                                $kcal = ($carbs * 3) + ($protein * 4) + ($fats * 9); 
                                echo round($kcal, 2);
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">No nutrition history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="record_pagination">
        <button class="arrow_btn" onclick="navigatePage(<?= $page - 1 ?>)" <?= $page <= 1 ? 'disabled' : '' ?>>
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="arrow_btn" onclick="navigatePage(<?= $page + 1 ?>)" <?= $page >= $totalPages ? 'disabled' : '' ?>>
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

<script>
    function navigatePage(page) {
        if (page >= 1 && page <= <?= $totalPages ?>) {
            window.location.href = '?page=' + page;
        }
    }
</script>

<?php include('../features/footer.php'); ?>
