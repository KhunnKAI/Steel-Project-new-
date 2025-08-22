<?php
// Turn off all error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unwanted output
ob_start();

// Set content type to JSON immediately
header('Content-Type: application/json; charset=utf-8');

try {
    // Check if config.php exists
    if (!file_exists('config.php')) {
        throw new Exception('Config file not found');
    }
    
    // Include config - this will start the session properly
    require_once 'config.php';
    
    // Check if database connection exists
    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }
    
    // Clean any unwanted output before JSON response
    ob_clean();
    
    $response = [
        'logged_in' => false,
        'admin_info' => null,
        'time_remaining' => 0
    ];

    if (isLoggedIn()) {
        $admin = getCurrentAdmin();
        
        if ($admin) {
            $time_remaining = 14400 - (time() - $_SESSION['login_time']); // 4 hours - elapsed time
            
            $response = [
                'logged_in' => true,
                'admin_info' => [
                    'admin_id' => $admin['admin_id'],
                    'fullname' => $admin['fullname'],
                    'position' => $admin['position'],
                    'department' => $admin['department']
                ],
                'time_remaining' => max(0, $time_remaining),
                'login_time' => $_SESSION['login_time'],
                'last_activity' => $_SESSION['last_activity'] ?? $_SESSION['login_time']
            ];
        }
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Clean any unwanted output before JSON response
    ob_clean();
    
    echo json_encode([
        'logged_in' => false,
        'error' => $e->getMessage(),
        'admin_info' => null,
        'time_remaining' => 0
    ], JSON_UNESCAPED_UNICODE);
}

// End output buffering and clean exit
ob_end_flush();
exit();
?>