<?php
// microservices/back-admision/lib/SessionHelper.php

class SessionHelper {
    
    public static function getUserId() {
        return $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
    }
    
    public static function getUserRole() {
        return $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
    }
    
    public static function getUserName() {
        $firstName = $_SESSION['first_name'] ?? $_SESSION['first_name'] ?? '';
        $lastName = $_SESSION['last_name'] ?? $_SESSION['last_name'] ?? '';
        return trim($firstName . ' ' . $lastName);
    }
    
    public static function getWorkArea() {
        return $_SESSION['work_area'] ?? null;
    }
    
    public static function isAuthenticated() {
        return self::getUserId() !== null;
    }
    
    public static function hasRole($allowedRoles) {
        $userRole = self::getUserRole();
        if (is_array($allowedRoles)) {
            return in_array($userRole, $allowedRoles);
        }
        return $userRole === $allowedRoles;
    }
}
?>