<?php
// api.php
// Optimized for 8 devices with a dynamic 2-month data retention guard window

date_default_timezone_set('Asia/Dhaka');


include('database.php'); 

$method = $_SERVER['REQUEST_METHOD'];

// --- 1. HANDLE ATTENDANCE UPLOADS (POST) ---
if ($method == 'POST') {
    $data = file_get_contents('php://input');
    
    // Log raw data immediately to verify connectivity
    file_put_contents(
        "zkteco_log.txt",
        date("Y-m-d H:i:s")."\n" . $data . "\n\n",
        FILE_APPEND
    );
    
    $device_sn = isset($_GET['SN']) ? trim($_GET['SN']) : (isset($_GET['sn']) ? trim($_GET['sn']) : 'UNKNOWN_SN');

    // MAP SERIAL NUMBERS TO REAL PHYSICAL LOCATIONS
    $device_mapping = [
        'AF4C200960096'   => 'STORE TEST - RZROKY',
        'AF4C200960092'   => 'Floor 8 - Entry - D#1',
        'AF4C200960070'   => 'Floor 8 - Exit - D#2',
        'AF4C200960094'   => 'Floor 7 - Exit - D#3',
        'AF4C200960097'   => 'Floor 7 - Entry - D#4',
        'AF4C200960061'   => 'CER ROOM Entry - D#5',
        'AF4C200960067'   => 'MCR Entry - D#6',
        'AF4C200960068'   => 'Hall Room - Device 1',
        'AF4C200960069'   => 'HRSOFTBD - RZROKY',
    ];

    $device_location = isset($device_mapping[$device_sn]) ? $device_mapping[$device_sn] : 'Unassigned Location';
    
    $lines = explode("\n", $data);
    
    $twoMonthsAgo = strtotime('-60 days'); 

    $all_saved = true;
    $error_message = "";

    // Open a fast transaction pool
    $conn->beginTransaction();

    try {
        $stmt = $conn->prepare("INSERT IGNORE INTO attendance_log (id_no, log_time, device_sn, device_location, status) VALUES (?, ?, ?, ?, ?)");

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'OPLOG') === 0) continue; 

            $fields = explode("\t", $line);

            if (count($fields) >= 4) {
                $id_no    = $fields[0];   
                $log_time = $fields[1]; // Format: 'YYYY-MM-DD HH:MM:SS'
                $status   = $fields[3];    
                
                // Convert the row log time string to a comparison timestamp
                $logTimestamp = strtotime($log_time);

                // 🔴 CRITICAL FILTER GUARD: Only insert if log date falls within the past 60 days
                if ($logTimestamp !== false && $logTimestamp >= $twoMonthsAgo) {
                    $stmt->execute([$id_no, $log_time, $device_sn, $device_location, $status]);
                }
                // Years like 2024 / 2025 fail this condition and are skipped safely
            }
        }
        
        $conn->commit();

    } catch (Exception $e) {
        $conn->rollBack();
        $all_saved = false;
        $error_message = $e->getMessage();
        
        file_put_contents(
            "db_errors.txt",
            date("Y-m-d H:i:s") . " | DB Error: " . $error_message . "\n",
            FILE_APPEND
        );
    }
    
    header("Content-Type: text/plain");
    if ($all_saved) {
        echo "OK"; // Device receives "OK", drops the batch from cache memory, and progresses forward
    } else {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Database Busy.";
    }
    exit();
} 

// --- 2. HANDLE SERVICE INITIALIZATION (GET) ---
elseif ($method == 'GET') {
    header("Content-Type: text/plain");
    
    // Core Registry Status
    echo "RegistryCode=NotRegister\n";
    
    // ⏳ FIX THE INTERVAL LOOPS: Force heartbeats to a 60-second cycle
    echo "Delay=120\n";                 // Main check-in interval (60 seconds)
    echo "ErrorDelay=60\n";            // Retry sleep period if a disconnect occurs
    echo "TransInterval=1\n";          // Queue transmission sweep rate
    echo "TransTimes=00:00;23:59\n";   
    echo "GetOption=1\n";
    
    // 🇧🇩 FIX THE TIME OFFSET: Standardized PUSH protocol codes for Dhaka (GMT+6)
    echo "TimeZone=6\n";               // Standard hour offset indicator
    echo "SetOption TimeZone=6\n";     // Secondary parameter block override
    echo "SET OPTIONS TimeZone=360\n"; // Fallback rule tracking calculation (in minutes: 6 * 60)
    
    exit();
} 

else {
    header("HTTP/1.1 405 Method Not Allowed");
    echo "Unsupported HTTP Method.";
}
?>