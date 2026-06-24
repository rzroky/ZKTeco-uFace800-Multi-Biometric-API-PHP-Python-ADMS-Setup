# ZKTeco-uFace800-Multi-Biometric-API-PHP-Python-ADMS-Setup
ZKTeco Multi-Biometric Device API | PHP + Python | ADMS Setup. IP Setup, DNS Setup 


# 🚀 Crash-Proof ZKTeco ADMS PHP Backend (High-Concurrency Edition)

An industrial-grade, native PHP implementation of the ZKTeco ADMS (Automatic Data Master Server) protocol designed to handle simultaneous, real-time log ingestion from multiple biometric devices (tested with 8+ machines syncing simultaneously every few seconds).

## ✨ Key Technical Enhancements
* **Anti-Collision Transaction Architecture:** Utilizes explicit InnoDB row-level transaction blocks (`beginTransaction` / `commit`) to prevent database multi-device read/write deadlocks.
* **Duplicate Elimination Protection Matrix:** Leverages MySQL compound unique keys combined with `INSERT IGNORE` processing logic to safely disregard connection duplicate resends.
* **HTTP 500 Network Retain Safety Valve:** When database traffic surges or connections briefly stutter, the backend gracefully signals an HTTP 500 error, instructing devices to store data locally and safely retry on subsequent loops.
* **On-Demand Dynamic Historical Recovery Engine:** Includes an isolated query routing script (`api_dynamic.php`) that overrides standard storage windows to pull precise historical user datasets or target date ranges on-demand.

---



💻 Hardware ADMS Configuration Settings

Access your biometric terminal's network configuration menus via the physical keypad interface (Comm. -> Cloud Server / ADMS Settings) and supply these properties:

1. SERVER MOOD :: ADMS
2. ENABLE DOMAIN NAME :: ON
3. Server Address: http://yourdomain.com/public/api.php (For normal production logs)
4. Enable Proxy Server :: OFF

<img width="1600" height="1401" alt="aa" src="https://github.com/user-attachments/assets/00f95c1b-edee-4212-a532-b3d0251f2ec0" />


💻 NETWORK Settings

Access your biometric terminal's network configuration menus via the physical keypad interface (Comm. -> Ethernet Setting) and supply these properties:

1. IP ADDRESS:: 192.168.77.111
2. SUBNET MASK:: 255.255.255.0
3. GATEWAY :: 192.168.77.1
4. DNS :: 1.1.1.1 or 8.8.8.8
5. TCP COMM.PORT :: 443 or 80 (for https 443, http 80)
6. DHCP :: OFF

<img width="1600" height="1180" alt="bb" src="https://github.com/user-attachments/assets/46caad90-3446-45e8-bdb8-3458cdd53504" />



⏳ HANDLE DEVICE CONFIGURATION
($method == 'GET') {
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


## 🗄️ Database Setup Script (`database.sql`)

Execute this SQL blueprint to configure your high-velocity ledger table with optimization indexes:

```sql
CREATE TABLE IF NOT EXISTS `attendance_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_no` VARCHAR(50) NOT NULL,
  `log_time` DATETIME NOT NULL,
  `device_sn` VARCHAR(100) NOT NULL,
  `device_location` VARCHAR(255) DEFAULT 'Unassigned Location',
  `status` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

-- CRITICAL: Prevent overlapping duplicate logs from multiple devices 
ALTER TABLE `attendance_log` ADD UNIQUE KEY `uid_time_sn` (`id_no`, `log_time`, `device_sn`);


