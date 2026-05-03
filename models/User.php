<?php
// ============================================================
// models/User.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll(int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT userID, username, email, role, fullName, phone, createdAt, isActive
             FROM users ORDER BY createdAt DESC LIMIT $limit OFFSET $offset"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByID(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT userID, username, email, role, fullName, phone, createdAt, isActive
             FROM users WHERE userID=?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [];
        if (isset($data['fullName'])) { $fields[] = 'fullName=?'; $params[] = $data['fullName']; }
        if (isset($data['email']))    { $fields[] = 'email=?';    $params[] = $data['email'];    }
        if (isset($data['phone']))    { $fields[] = 'phone=?';    $params[] = $data['phone'];    }
        if (isset($data['role']))     { $fields[] = 'role=?';     $params[] = $data['role'];     }
        if (isset($data['isActive'])) { $fields[] = 'isActive=?'; $params[] = $data['isActive']; }
        if (isset($data['password']) && $data['password']) {
            $fields[] = 'password=?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (!$fields) return false;
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(',', $fields) . " WHERE userID=?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("UPDATE users SET isActive=0 WHERE userID=?");
        return $stmt->execute([$id]);
    }

    public function count(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function getVehiclesForUser(int $userID): array {
        $stmt = $this->db->prepare("SELECT * FROM vehicles WHERE userID=?");
        $stmt->execute([$userID]);
        return $stmt->fetchAll();
    }
}
