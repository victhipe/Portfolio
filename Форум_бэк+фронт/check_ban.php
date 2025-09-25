<?php
function is_banned($conn, $user_id = null, $ip_address = null) {
    $query = "SELECT * FROM bans WHERE ";
    $params = [];
    
    if ($user_id) {
        $query .= "user_id = :user_id";
        $params[':user_id'] = $user_id;
    } elseif ($ip_address) {
        $query .= "ip_address = :ip_address";
        $params[':ip_address'] = $ip_address;
    } else {
        return false;
    }
    
    $query .= " AND (expiration_date IS NULL OR expiration_date > NOW())";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetch() !== false;
}