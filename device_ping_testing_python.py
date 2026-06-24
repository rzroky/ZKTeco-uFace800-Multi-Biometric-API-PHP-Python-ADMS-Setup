from zk import ZK
from datetime import datetime

DEVICE_IP = "192.168.110.111"
DEVICE_PORT = 443

zk = ZK(
    DEVICE_IP,
    port=DEVICE_PORT,
    timeout=10,
    password=0,
    force_udp=False
)

conn = None

try:
    print("Connecting to device...")

    conn = zk.connect()

    print("Connected successfully")

    # Get attendance logs
    attendance = conn.get_attendance()

    print(f"Total logs: {len(attendance)}")

    print("-------------------------------------")

    for log in attendance:
        if(log.user_id != 100001):
            print(
                "User ID:",
                log.user_id,
                "| Time:",
                log.timestamp,
                "| Status:",
                log.status,
                "| Punch:",
                log.punch
            )

    print("-------------------------------------")

except Exception as e:
    print("Error:", e)

finally:
    if conn:
        conn.disconnect()
        print("Disconnected")
