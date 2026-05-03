<?php
// ============================================================
// models/ParkingRecord.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class ParkingRecord {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll(string $status = '', int $limit = 50, int $offset = 0): array {
        $sql = "SELECT pr.*, v.licensePlate, v.vehicleType, v.ownerName,
                       ps.slotNumber, pl.lotName,
                       p.amount, p.paymentMethod, p.status AS payStatus
                FROM parking_records pr
                JOIN vehicles v ON pr.vehicleID = v.vehicleID
                JOIN parking_slots ps ON pr.slotID = ps.slotID
                JOIN parking_lots pl ON ps.lotID = pl.lotID
                LEFT JOIN payments p ON pr.recordID = p.recordID
                WHERE 1=1";
        $params = [];
        if ($status) {
            $sql .= " AND pr.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY pr.entryTime DESC LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getActive(): array {
        $stmt = $this->db->query("SELECT * FROM vw_active_parking ORDER BY entryTime DESC");
        return $stmt->fetchAll();
    }

    public function getByID(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT pr.*, v.licensePlate, v.vehicleType, v.ownerName, v.ownerPhone,
                    ps.slotNumber, ps.slotType, pl.lotName,
                    p.paymentID, p.amount, p.paymentMethod, p.status AS payStatus, p.transactionRef
             FROM parking_records pr
             JOIN vehicles v ON pr.vehicleID = v.vehicleID
             JOIN parking_slots ps ON pr.slotID = ps.slotID
             JOIN parking_lots pl ON ps.lotID = pl.lotID
             LEFT JOIN payments p ON pr.recordID = p.recordID
             WHERE pr.recordID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Vehicle check-in
     */
    public function checkIn(int $vehicleID, int $slotID, string $paymentMethod = 'cash'): array {
        try {
            $this->db->beginTransaction();

            // Check if vehicle already parked
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM parking_records WHERE vehicleID=? AND status='active'"
            );
            $stmt->execute([$vehicleID]);
            if ($stmt->fetchColumn() > 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Vehicle already has an active parking session.'];
            }

            // Check slot availability
            $stmt = $this->db->prepare("SELECT status FROM parking_slots WHERE slotID=?");
            $stmt->execute([$slotID]);
            $slot = $stmt->fetch();
            if (!$slot || $slot['status'] !== 'available') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Parking slot is not available.'];
            }

            // Create record
            $stmt = $this->db->prepare(
                "INSERT INTO parking_records (vehicleID, slotID, entryTime, status) VALUES (?, ?, NOW(), 'active')"
            );
            $stmt->execute([$vehicleID, $slotID]);
            $recordID = (int) $this->db->lastInsertId();

            // Update slot status
            $stmt = $this->db->prepare("UPDATE parking_slots SET status='occupied' WHERE slotID=?");
            $stmt->execute([$slotID]);

            // Create pending payment
            $stmt = $this->db->prepare(
                "INSERT INTO payments (recordID, amount, paymentMethod, status) VALUES (?, 0, ?, 'pending')"
            );
            $stmt->execute([$recordID, $paymentMethod]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Vehicle checked in successfully.', 'recordID' => $recordID];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Check-in failed: ' . $e->getMessage()];
        }
    }

    /**
     * Vehicle check-out and payment
     */
    public function checkOut(int $recordID, string $paymentMethod = 'cash'): array {
        try {
            $this->db->beginTransaction();

            $record = $this->getByID($recordID);
            if (!$record || $record['status'] !== 'active') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Invalid or already completed record.'];
            }

            $exitTime = date('Y-m-d H:i:s');
            $minutes  = (int) ceil((strtotime($exitTime) - strtotime($record['entryTime'])) / 60);
            $fee      = calculateFee($minutes);

            // Update record
            $stmt = $this->db->prepare(
                "UPDATE parking_records SET exitTime=?, status='completed' WHERE recordID=?"
            );
            $stmt->execute([$exitTime, $recordID]);

            // Update slot
            $stmt = $this->db->prepare("UPDATE parking_slots SET status='available' WHERE slotID=?");
            $stmt->execute([$record['slotID']]);

            // Update payment
            $ref = 'TXN-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $this->db->prepare(
                "UPDATE payments SET amount=?, paymentMethod=?, paymentTime=NOW(), status='paid', transactionRef=?
                 WHERE recordID=?"
            );
            $stmt->execute([$fee, $paymentMethod, $ref, $recordID]);

            $this->db->commit();
            return [
                'success'   => true,
                'message'   => 'Vehicle checked out successfully.',
                'fee'       => $fee,
                'duration'  => $minutes,
                'txnRef'    => $ref,
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Check-out failed: ' . $e->getMessage()];
        }
    }

    public function count(string $status = ''): int {
        $sql = "SELECT COUNT(*) FROM parking_records";
        $params = [];
        if ($status) {
            $sql .= " WHERE status=?";
            $params[] = $status;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getDashboardStats(): array {
        $stmt = $this->db->query("SELECT * FROM vw_slot_overview");
        $lots = $stmt->fetchAll();

        $totals = ['totalSlots' => 0, 'available' => 0, 'occupied' => 0, 'maintenance' => 0];
        foreach ($lots as $lot) {
            $totals['totalSlots']   += $lot['totalSlots'];
            $totals['available']    += $lot['availableSlots'];
            $totals['occupied']     += $lot['occupiedSlots'];
            $totals['maintenance']  += $lot['maintenanceSlots'];
        }

        $stmt = $this->db->query("SELECT COUNT(*) FROM vehicles");
        $totals['totalVehicles'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query(
            "SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='paid'"
        );
        $totals['totalRevenue'] = (float) $stmt->fetchColumn();

        $stmt = $this->db->query(
            "SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='paid' AND DATE(paymentTime)=CURDATE()"
        );
        $totals['todayRevenue'] = (float) $stmt->fetchColumn();

        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM parking_records WHERE status='active'"
        );
        $totals['activeSessions'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM parking_records WHERE DATE(entryTime)=CURDATE()"
        );
        $totals['todayParked'] = (int) $stmt->fetchColumn();

        return $totals;
    }

    public function getRecentActivity(int $limit = 10): array {
        $stmt = $this->db->prepare(
            "SELECT pr.recordID, pr.entryTime, pr.exitTime, pr.status,
                    v.licensePlate, v.ownerName, ps.slotNumber, pl.lotName, p.amount
             FROM parking_records pr
             JOIN vehicles v ON pr.vehicleID = v.vehicleID
             JOIN parking_slots ps ON pr.slotID = ps.slotID
             JOIN parking_lots pl ON ps.lotID = pl.lotID
             LEFT JOIN payments p ON pr.recordID = p.recordID
             ORDER BY pr.entryTime DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}

// ============================================================
// models/Payment.php (inline for brevity)
// ============================================================
class Payment {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll(string $status = '', int $limit = 50, int $offset = 0): array {
        $sql = "SELECT p.*, v.licensePlate, v.ownerName, ps.slotNumber, pl.lotName,
                       pr.entryTime, pr.exitTime
                FROM payments p
                JOIN parking_records pr ON p.recordID = pr.recordID
                JOIN vehicles v ON pr.vehicleID = v.vehicleID
                JOIN parking_slots ps ON pr.slotID = ps.slotID
                JOIN parking_lots pl ON ps.lotID = pl.lotID
                WHERE 1=1";
        $params = [];
        if ($status) {
            $sql .= " AND p.status=?";
            $params[] = $status;
        }
        $sql .= " ORDER BY p.paymentTime DESC LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRevenueSummary(): array {
        $stmt = $this->db->query("SELECT * FROM vw_daily_revenue LIMIT 30");
        return $stmt->fetchAll();
    }

    public function getByMethod(): array {
        $stmt = $this->db->query(
            "SELECT paymentMethod, COUNT(*) AS transactions, IFNULL(SUM(amount),0) AS revenue
             FROM payments WHERE status='paid' GROUP BY paymentMethod"
        );
        return $stmt->fetchAll();
    }

    public function getTotalRevenue(): float {
        $stmt = $this->db->query("SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='paid'");
        return (float) $stmt->fetchColumn();
    }

    public function getMonthlyRevenue(): array {
        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(paymentTime,'%Y-%m') AS month,
                    COUNT(*) AS transactions, SUM(amount) AS revenue
             FROM payments WHERE status='paid'
             GROUP BY DATE_FORMAT(paymentTime,'%Y-%m')
             ORDER BY month DESC LIMIT 12"
        );
        return $stmt->fetchAll();
    }
}
