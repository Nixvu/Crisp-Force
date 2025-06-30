<?php
// Sanitize input
function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

// Get user data
function getUserData($user_id) {
    global $conn;
    
    $sql = "SELECT * FROM User WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Get customer data
function getCustomerData($user_id) {
    global $conn;
    
    $sql = "SELECT c.* FROM Customer c 
            JOIN User u ON c.id_user = u.id_user 
            WHERE u.id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>