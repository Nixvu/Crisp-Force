<?php
require_once 'config.php';
require_once 'functions.php';

// Login function
function login($email, $password) {
    global $conn;
    
    $email = sanitize($email);
    $password = sanitize($password);
    
    $sql = "SELECT * FROM User WHERE email = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['nama_lengkap'];
            
            // Check if password needs to be updated
            if ($user['update_password']) {
                $_SESSION['force_password_change'] = true;
                return 'change_password';
            }
            
            return 'success';
        }
    }
    
    return 'invalid_credentials';
}

// Register function (only for customers)
function register($name, $email, $phone, $password) {
    global $conn;
    
    $name = sanitize($name);
    $email = sanitize($email);
    $phone = sanitize($phone);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into User table
        $sql_user = "INSERT INTO User (nama_lengkap, email, no_hp, password, role) VALUES (?, ?, ?, ?, 'Customer')";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("ssss", $name, $email, $phone, $hashed_password);
        $stmt_user->execute();
        $user_id = $stmt_user->insert_id;
        
        // Insert into Customer table
        $sql_customer = "INSERT INTO Customer (id_user, segmentasi) VALUES (?, 'baru')";
        $stmt_customer = $conn->prepare($sql_customer);
        $stmt_customer->bind_param("i", $user_id);
        $stmt_customer->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function checkRole($allowed_roles) {
    if (!isLoggedIn() || !in_array($_SESSION['user_role'], $allowed_roles)) {
        header("Location: ../login.php");
        exit();
    }
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>