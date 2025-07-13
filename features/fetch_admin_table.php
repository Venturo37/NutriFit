<?php
// NAME: Ms. CHOW YAN PING
// Project name: Nutrifit
// DESCRIPTION OF PROGRAM: Fetches and returns admin account records for the admin user management interface. 
//                         Includes pagination and dynamic loading of admin info.
// FIRST WRITTEN: 2/6/2025
// LAST MODIFIED: 9/7/2025
include('connection.php');

$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$result = $connection->query("SELECT * FROM admin_t LIMIT $limit OFFSET $offset");
$totalResult = $connection->query("SELECT COUNT(*) AS total FROM admin_t");
$total = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Table
echo '<h3 class="table_title">Admin Table
    <button class="add_admin_btn"><i class="fas fa-plus"></i> Add Admin</button>
</h3>
<table class="record_table usermanage_theme">
    <thead>
        <tr>
            <th>Admin ID</th>
            <th>Admin Name</th>
            <th>Email</th>
            <th></th>
        </tr>
    </thead>
    <tbody>';

while ($row = $result->fetch_assoc()) {
    echo '<tr data-id="' . $row['adm_id'] . '" data-type="admin">
        <td>' . 'AD' . str_pad($row['adm_id'], 3, '0', STR_PAD_LEFT) . '</td>
        <td>' . htmlspecialchars($row['adm_name']) . '</td>
        <td>' . htmlspecialchars($row['adm_email']) . '</td>
        <td>
            <button class="admin_info_btn"><i class="fas fa-info"></i></button>
            <button class="del_btn">Delete</button>
        </td>
    </tr>';
}

echo '</tbody></table>';

// Pagination
echo '<div class="admin_record_pagination">';
echo '<button class="arrow_btn admin_page_btn" data-page="' . ($page - 1) . '"' . ($page <= 1 ? ' disabled' : '') . '><i class="fas fa-chevron-left"></i></button>';
echo '<button class="arrow_btn admin_page_btn" data-page="' . ($page + 1) . '"' . ($page >= $totalPages ? ' disabled' : '') . '><i class="fas fa-chevron-right"></i></button>';
echo '</div>';
?>
