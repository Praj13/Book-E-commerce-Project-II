<?php
include 'config.php'; // Your database config

header('Content-Type: application/json');

if (isset($_GET['term']) && !empty($_GET['term'])) {
    $term = mysqli_real_escape_string($conn, $_GET['term']);
    
    // Fetch matching product names (limit to 5 suggestions)
    $query = "SELECT name FROM products WHERE name LIKE '%$term%' LIMIT 5";
    $result = mysqli_query($conn, $query);
    
    $suggestions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $suggestions[] = $row['name'];
    }
    
    echo json_encode($suggestions);
    exit;
}

echo json_encode([]); // Return empty array if no term
?>