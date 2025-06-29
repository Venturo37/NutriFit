<?php
    include('connection.php');

    $limit = 7;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) :1;
    $offset = ($page - 1) * $limit;

    $sub_query = "SELECT 
        a.adm_id AS user_id, 
        ad.adm_name AS user_name, 
        'Updated Profile' AS action, 
        acc_mana_timestamp AS timestamp
    FROM account_management_t a LEFT JOIN admin_t ad ON a.adm_id = ad.adm_id
    WHERE a.adm_id IS NOT NULL

    UNION ALL 

    SELECT 
        a.usr_id AS user_id, 
        u.usr_name AS user_name, 
        'Updated Profile' AS action, 
        acc_mana_timestamp AS timestamp
    FROM account_management_t a LEFT JOIN user_t u ON a.usr_id = u.usr_id
    WHERE a.usr_id IS NOT NULL
    
    UNION ALL

    SELECT
        a.adm_id, 
        ad.adm_name, 
        CONCAT(a.adm_mana_action, ' Admin ', a.managed_adm_id),
        a.adm_mana_timestamp
    FROM admin_management_t a JOIN admin_t ad ON a.adm_id = ad.adm_id

    UNION ALL

    SELECT 
        c.adm_id, 
        ad.adm_name, 
        CONCAT(c.cate_mana_action, ' Category ', c.cate_id),
        c.cate_mana_timestamp
    FROM category_management_t c JOIN admin_t ad ON c.adm_id = ad.adm_id

    UNION ALL

    SELECT 
        f.usr_id, 
        u.usr_name, 
        'Sent Feedback', 
        f.fdbk_timestamp
    FROM feedback_t f JOIN user_t u ON f.usr_id = u.usr_id

    UNION ALL

    SELECT 
        mm.adm_id, 
        ad.adm_name, 
        CONCAT(mm.meal_mana_action, ' Meal ', mm.meal_id),
        mm.meal_mana_timestamp
    FROM meal_management_t mm JOIN admin_t ad ON mm.adm_id = ad.adm_id

    UNION ALL

    SELECT
        mt.adm_id, 
        ad.adm_name, 
        CONCAT(mt.mltm_mana_action, ' Meal Time ', mt.mltm_id),
        mt.mltm_mana_timestamp
    FROM meal_time_management_t mt JOIN admin_t ad ON mt.adm_id = ad.adm_id

    UNION ALL 

    SELECT
        pm.adm_id, 
        ad.adm_name, 
        CONCAT(pm.pic_mana_action, ' Profile Pic ', pm.pic_id),
        pm.pic_mana_timestamp
    FROM picture_management_t pm JOIN admin_t ad ON pm.adm_id = ad.adm_id

    UNION ALL

    SELECT
        rp.usr_id,
        u.usr_name, 
        'Reset Password', 
        rp.rst_pass_log_created
    FROM reset_password_log_t rp JOIN user_t u ON rp.usr_id = u.usr_id
    WHERE rp.usr_id IS NOT NULL

    UNION ALL

    SELECT
        rp.adm_id,
        ad.adm_name, 
        'Reset Password', 
        rp.rst_pass_log_created
    FROM reset_password_log_t rp JOIN admin_t ad ON rp.adm_id = ad.adm_id
    WHERE rp.adm_id IS NOT NULL

    UNION ALL

    SELECT
        um.adm_id, 
        ad.adm_id, 
        CONCAT('Deleted User', um.usr_id),
        um.usr_mana_timestamp
    FROM user_management_t um JOIN admin_t ad ON um.adm_id = ad.adm_id

    UNION ALL

    SELECT
        umi.usr_id, 
        u.usr_name,
        'Ate a Meal', 
        umi.mlog_timestamp
    FROM user_meal_intake_t umi JOIN user_t u ON (umi.usr_id IS NOT NULL AND umi.usr_id = u.usr_id)
    WHERE umi.usr_id IS NOT NULL

    UNION ALL

    SELECT
        mi.usr_id, 
        u.usr_name,
        'Ate a Meal', 
        umi.mlog_timestamp
    FROM user_meal_intake_t umi JOIN manual_input_t mi ON umi.manual_id = mi.manual_id
    JOIN user_t u ON mi.usr_id = u.usr_id
    WHERE umi.manual_id IS NOT NULL

    UNION ALL

    SELECT
        uw.usr_id, 
        u.usr_name,
        'Updated Weight', 
        uw.weight_log_date
    FROM user_weight_log_t uw JOIN user_t u ON uw.usr_id = u.usr_id

    UNION ALL

    SELECT 
        uws.usr_id, 
        u.usr_name, 
        'Started Session', 
        uws.wlog_timestamp
    FROM user_workout_session_t uws JOIN user_t u ON uws.usr_id = u.usr_id

    UNION ALL

    SELECT
        wm.adm_id, 
        ad.adm_id, 
        CONCAT(wm.work_mana_action, ' Workout ', wm.work_id),
        wm.work_mana_timestamp
    FROM workout_management_t wm JOIN admin_t ad ON wm.adm_id = ad.adm_id
    ";

    $activity_log_query = "SELECT * FROM (
        $sub_query
    ) AS activity_log
    ORDER BY timestamp DESC
    LIMIT $limit OFFSET $offset
    ";

    $count_query = "SELECT COUNT(*) AS total FROM (
        $sub_query
    ) AS total_activity
    ";

    $activity_log_result = $connection-> query($activity_log_query);
    if (!$activity_log_result) {
        die("Query failed". $connection->error);
    }
    $count_result = $connection->query($count_query);
    $total_rows = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);

    echo "
        <table border='1'>
    ";
    echo "
        <tr>
            <th>User (ID, Name)</th>
            <th>Action</th>
            <th>Timestamp</th>
        </tr>
    ";
    while ($row = $activity_log_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['user_id']) .  ", " . htmlspecialchars($row['user_name']) ."</td>";
        echo "<td>" . htmlspecialchars($row['action']) . "</td>";
        echo "<td>" . htmlspecialchars($row['timestamp']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo '<div class="system_activity_pagination">';
    echo '<button class="activity_page_btn" data-page=" '. ($page - 1) .' " '. ($page <= 1 ? 'disabled' : '') .'><i class="fa-solid fa-chevron-left"></i></button>';
    echo '<button class="activity_page_btn" data-page=" '. ($page + 1) .' " '. ($page >= $total_pages ? 'disabled' : '') .'><i class="fa-solid fa-chevron-right"></i></button>';
    echo '</div>';
?>