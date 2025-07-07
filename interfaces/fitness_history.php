<?php
// session_start();
include('../features/connection.php');

include('../features/restriction.php');
$usr_id = $_SESSION['usr_id'];

include('../features/header.php');

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Count total records
$count_query = "SELECT COUNT(*) AS total FROM user_workout_session_t WHERE usr_id = $usr_id";
$count_result = mysqli_query($connection, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch paginated data
$query = "
    SELECT 
        uws.wlog_timestamp, 
        wt.work_name, 
        wt.cate_id, 
        uws.wlog_calories_burned, 
        uws.wlog_duration, 
        uws.wlog_intensity
    FROM user_workout_session_t uws
    JOIN workout_t wt ON uws.work_id = wt.work_id
    WHERE uws.usr_id = $usr_id
    ORDER BY uws.wlog_timestamp ASC
    LIMIT $limit OFFSET $offset
";
$result = mysqli_query($connection, $query);
?>

<div class="record_header">
    <h3><i class="fas fa-circle-arrow-left" onclick="window.location.href='user_profile.php'"></i> Fitness Activity History</h3>
</div>

<div id="content">
    <div class="record_table_wrapper fitness_history">
        <table class="record_table">
            <thead>
                <tr>
                    <th>Date&Time</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Kcal Burned</th>
                    <th>Duration(s)</th>
                    <th>Level</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= date('d/m/y H:i', strtotime($row['wlog_timestamp'])) ?></td>
                            <td><?= htmlspecialchars($row['work_name']) ?></td>
                            <td><?= htmlspecialchars($row['cate_id']) ?></td>
                            <td><?= $row['wlog_calories_burned'] ?></td>
                            <td><?= round($row['wlog_duration'] * 60) ?></td>
                            <td><?= htmlspecialchars($row['wlog_intensity']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">No fitness history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="record_pagination">
        <button class="arrow_btn" onclick="navigatePage(<?= $page - 1 ?>)" <?= $page <= 1 ? 'disabled' : '' ?>>
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="arrow_btn" onclick="navigatePage(<?= $page + 1 ?>)" <?= $page >= $total_pages ? 'disabled' : '' ?>>
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

<script>
    function navigatePage(page) {
        if (page >= 1 && page <= <?= $total_pages ?>) {
            window.location.href = '?page=' + page;
        }
    }
</script>

<?php include('../features/footer.php'); ?>
