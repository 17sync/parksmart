<?php
// ============================================================
// models/Vehicle.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class Vehicle {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll(string $search = '', int $limit = 50, int $offset = 0): array {
        $sql = "SELECT v.*, u.username FROM vehicles v
                LEFT JOIN users u ON v.userID = u.userID";
        $params = [];
        if ($search) {
            $sql .= " WHERE v.licensePlate LIKE ? OR v.ownerName LIKE ?";
            $params = ["%$search%", "%$search%"];
        }
        $sql .= " ORDER BY v.createdAt DESC LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByID(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM vehicles WHERE vehicleID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByLicense(string $plate): ?array {
        $stmt = $this->db->prepare("SELECT * FROM vehicles WHERE licensePlate = ?");
        $stmt->execute([$plate]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO vehicles (licensePlate, vehicleType, ownerName, ownerPhone, ownerEmail, userID)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            strtoupper(trim($data['licensePlate'])),
            $data['vehicleType'],
            $data['ownerName'],
            $data['ownerPhone'] ?? null,
            $data['ownerEmail'] ?? null,
            $data['userID']     ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE vehicles SET licensePlate=?, vehicleType=?, ownerName=?, ownerPhone=?, ownerEmail=?
             WHERE vehicleID=?"
        );
        return $stmt->execute([
            strtoupper(trim($data['licensePlate'])),
            $data['vehicleType'],
            $data['ownerName'],
            $data['ownerPhone'] ?? null,
            $data['ownerEmail'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): bool {
        // Check for active records
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM parking_records WHERE vehicleID=? AND status='active'"
        );
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) return false;
        $stmt = $this->db->prepare("DELETE FROM vehicles WHERE vehicleID=?");
        return $stmt->execute([$id]);
    }

    public function count(string $search = ''): int {
        $sql = "SELECT COUNT(*) FROM vehicles";
        $params = [];
        if ($search) {
            $sql .= " WHERE licensePlate LIKE ? OR ownerName LIKE ?";
            $params = ["%$search%", "%$search%"];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getParkingHistory(int $vehicleID): array {
        $stmt = $this->db->prepare(
            "SELECT pr.*, ps.slotNumber, pl.lotName, p.amount, p.paymentMethod, p.status as payStatus
             FROM parking_records pr
             JOIN parking_slots ps ON pr.slotID = ps.slotID
             JOIN parking_lots pl ON ps.lotID = pl.lotID
             LEFT JOIN payments p ON pr.recordID = p.recordID
             WHERE pr.vehicleID = ?
             ORDER BY pr.entryTime DESC"
        );
        $stmt->execute([$vehicleID]);
        return $stmt->fetchAll();
    }

    public function hasActiveSession(int $vehicleID): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM parking_records WHERE vehicleID=? AND status='active'"
        );
        $stmt->execute([$vehicleID]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
