<!-- 
NAME: CHOW YAN PING
Project name: Nutrifit
DESCRIPTION OF PROGRAM: Retrieves and returns user data from the database for display in the admin user management interface. 
                        Includes pagination and dynamic rendering of user info.
FIRST WRITTEN: 2/6/2025
LAST MODIFIED: 9/7/2025 
-->

<?php
include('connection.php');

$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$result = $connection->query("
    SELECT u.*, (
        SELECT uwl.weight_log_weight 
        FROM user_weight_log_t uwl 
        WHERE uwl.usr_id = u.usr_id 
        ORDER BY uwl.weight_log_date DESC 
        LIMIT 1
    ) AS latest_weight
    FROM user_t u
    LIMIT $limit OFFSET $offset
");

$totalResult = $connection->query("SELECT COUNT(*) AS total FROM user_t");
$total = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Table
echo '<h3 class="table_title">User Table</h3>
<table class="record_table usermanage_theme">
    <thead>
        <tr>
            <th>User ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Weight (KG)</th>
            <th>Height (CM)</th>
            <th></th>
        </tr>
    </thead>
    <tbody>';

while ($row = $result->fetch_assoc()) {
    $birth_year = (int)substr($row['usr_birthdate'], 0, 4);
    $age = (int)date('Y') - $birth_year;

    echo '<tr data-id="' . $row['usr_id'] . '" data-type="user">
        <td>' . 'UI' . str_pad($row['usr_id'], 3, '0', STR_PAD_LEFT) . '</td>
        <td>' . htmlspecialchars($row['usr_name']) . '</td>
        <td>' . htmlspecialchars($row['usr_email']) . '</td>
        <td>' . $age . '</td>
        <td>' . $row['usr_gender'] . '</td>
        <td>' . ($row['latest_weight'] !== null ? $row['latest_weight'] : '-') . '</td>
        <td>' . $row['usr_height'] . '</td>
        <td>
            <button class="info_btn"><i class="fas fa-info"></i></button>
            <button class="del_btn">Delete</button>
        </td>
    </tr>';
}

echo '</tbody></table>';

// Pagination
echo '<div class="admin_record_pagination">';
echo '<button class="arrow_btn user_page_btn" data-page="' . ($page - 1) . '"' . ($page <= 1 ? ' disabled' : '') . '><i class="fas fa-chevron-left"></i></button>';
echo '<button class="arrow_btn user_page_btn" data-page="' . ($page + 1) . '"' . ($page >= $totalPages ? ' disabled' : '') . '><i class="fas fa-chevron-right"></i></button>';
echo '</div>';
?>
