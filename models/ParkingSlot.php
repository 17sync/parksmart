<?php
// ============================================================
// models/ParkingSlot.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class ParkingSlot {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll(string $status = '', int $lotID = 0): array {
        $sql = "SELECT ps.*, pl.lotName FROM parking_slots ps
                JOIN parking_lots pl ON ps.lotID = pl.lotID WHERE 1=1";
        $params = [];
        if ($status) {
            $sql .= " AND ps.status = ?";
            $params[] = $status;
        }
        if ($lotID) {
            $sql .= " AND ps.lotID = ?";
            $params[] = $lotID;
        }
        $sql .= " ORDER BY pl.lotName, ps.slotNumber";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAvailable(): array {
        return $this->getAll('available');
    }

    public function getByID(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT ps.*, pl.lotName FROM parking_slots ps
             JOIN parking_lots pl ON ps.lotID = pl.lotID
             WHERE ps.slotID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO parking_slots (lotID, slotNumber, location, status, slotType)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['lotID'],
            strtoupper(trim($data['slotNumber'])),
            $data['location'],
            $data['status']   ?? 'available',
            $data['slotType'] ?? 'standard',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE parking_slots SET lotID=?, slotNumber=?, location=?, status=?, slotType=?
             WHERE slotID=?"
        );
        return $stmt->execute([
            $data['lotID'],
            strtoupper(trim($data['slotNumber'])),
            $data['location'],
            $data['status'],
            $data['slotType'],
            $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM parking_records WHERE slotID=? AND status='active'"
        );
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) return false;
        $stmt = $this->db->prepare("DELETE FROM parking_slots WHERE slotID=?");
        return $stmt->execute([$id]);
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE parking_slots SET status=? WHERE slotID=?");
        return $stmt->execute([$status, $id]);
    }

    public function getStats(): array {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status='available' THEN 1 ELSE 0 END) AS available,
                SUM(CASE WHEN status='occupied' THEN 1 ELSE 0 END) AS occupied,
                SUM(CASE WHEN status='maintenance' THEN 1 ELSE 0 END) AS maintenance
             FROM parking_slots"
        );
        return $stmt->fetch();
    }

    public function getLots(): array {
        $stmt = $this->db->query("SELECT * FROM parking_lots ORDER BY lotName");
        return $stmt->fetchAll();
    }

    public function getLotByID(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM parking_lots WHERE lotID=?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function createLot(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO parking_lots (lotName, capacity, location) VALUES (?, ?, ?)"
        );
        $stmt->execute([$data['lotName'], $data['capacity'], $data['location']]);
        return (int) $this->db->lastInsertId();
    }

    public function getMostUsed(int $limit = 5): array {
        $stmt = $this->db->prepare(
            "SELECT ps.slotNumber, pl.lotName, COUNT(pr.recordID) AS usageCount
             FROM parking_records pr
             JOIN parking_slots ps ON pr.slotID = ps.slotID
             JOIN parking_lots pl ON ps.lotID = pl.lotID
             GROUP BY pr.slotID
             ORDER BY usageCount DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getSlotOverview(): array {
        $stmt = $this->db->query("SELECT * FROM vw_slot_overview");
        return $stmt->fetchAll();
    }
}
