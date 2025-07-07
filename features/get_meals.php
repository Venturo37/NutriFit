<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('connection.php');

// Initialize values
$meal_time_id = isset($_GET['meal_time']) ? (int)$_GET['meal_time'] : 1;
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$meals = [];

// Base SQL
$query = "SELECT * FROM meal_t WHERE 1=1";
$params = [];
$types = "";

// Filter by meal time (handle 'Others')
if ($meal_time_id === 4) {
    $query .= " AND mltm_id NOT IN (1, 2, 3)";
} else {
    $query .= " AND mltm_id = ?";
    $params[] = $meal_time_id;
    $types .= "i";
}

// Search filter
if ($search_query !== '') {
    $query .= " AND meal_name LIKE ?";
    $params[] = "%" . $search_query . "%";
    $types .= "s";
}

// Prepare and bind safely
$stmt = $connection->prepare($query);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to prepare statement."]);
    exit;
}

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$finfo = new finfo(FILEINFO_MIME_TYPE);

while ($row = $result->fetch_assoc()) {
    if (!empty($row['meal_image'])) {
        $mime_type = $finfo->buffer($row['meal_image']);
        $row['meal_image'] = 'data:' . $mime_type . ';base64,' . base64_encode($row['meal_image']);
    } else {
        $row['meal_image'] = 'https://placehold.co/300x200/e2e8f0/e2e8f0?text=No+Image';
    }

    $meals[] = $row;
}

$stmt->close();
$connection->close();

header('Content-Type: application/json');
echo json_encode($meals);
?>