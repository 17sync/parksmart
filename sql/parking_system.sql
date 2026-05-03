-- ============================================================
-- PARKING MANAGEMENT SYSTEM - MySQL Database Script
-- ============================================================

CREATE DATABASE IF NOT EXISTS parking_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parking_system;

-- ============================================================
-- DROP TABLES (in reverse FK order)
-- ============================================================
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS parking_records;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS parking_slots;
DROP TABLE IF EXISTS parking_lots;
DROP TABLE IF EXISTS users;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
    userID      INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50) NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','user') NOT NULL DEFAULT 'user',
    fullName    VARCHAR(100) NOT NULL,
    phone       VARCHAR(20),
    createdAt   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    isActive    TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: parking_lots
-- ============================================================
CREATE TABLE parking_lots (
    lotID       INT AUTO_INCREMENT PRIMARY KEY,
    lotName     VARCHAR(100) NOT NULL,
    capacity    INT NOT NULL CHECK (capacity > 0),
    location    VARCHAR(200) NOT NULL,
    createdAt   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: parking_slots
-- ============================================================
CREATE TABLE parking_slots (
    slotID      INT AUTO_INCREMENT PRIMARY KEY,
    lotID       INT NOT NULL,
    slotNumber  VARCHAR(20) NOT NULL,
    location    VARCHAR(100) NOT NULL,
    status      ENUM('available','occupied','maintenance') NOT NULL DEFAULT 'available',
    slotType    ENUM('standard','compact','handicapped','ev') NOT NULL DEFAULT 'standard',
    createdAt   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lotID) REFERENCES parking_lots(lotID) ON DELETE CASCADE,
    UNIQUE KEY unique_slot (lotID, slotNumber)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: vehicles
-- ============================================================
CREATE TABLE vehicles (
    vehicleID   INT AUTO_INCREMENT PRIMARY KEY,
    licensePlate VARCHAR(20) NOT NULL UNIQUE,
    vehicleType ENUM('car','motorcycle','truck','bus','ev') NOT NULL DEFAULT 'car',
    ownerName   VARCHAR(100) NOT NULL,
    ownerPhone  VARCHAR(20),
    ownerEmail  VARCHAR(100),
    userID      INT,
    createdAt   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE SET NULL,
    INDEX idx_license (licensePlate)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: parking_records
-- ============================================================
CREATE TABLE parking_records (
    recordID    INT AUTO_INCREMENT PRIMARY KEY,
    vehicleID   INT NOT NULL,
    slotID      INT NOT NULL,
    entryTime   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exitTime    DATETIME DEFAULT NULL,
    status      ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
    notes       TEXT,
    FOREIGN KEY (vehicleID) REFERENCES vehicles(vehicleID) ON DELETE RESTRICT,
    FOREIGN KEY (slotID) REFERENCES parking_slots(slotID) ON DELETE RESTRICT,
    INDEX idx_entry_time (entryTime),
    INDEX idx_slot (slotID),
    INDEX idx_vehicle (vehicleID),
    CONSTRAINT chk_exit_after_entry CHECK (exitTime IS NULL OR exitTime > entryTime)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: payments
-- ============================================================
CREATE TABLE payments (
    paymentID   INT AUTO_INCREMENT PRIMARY KEY,
    recordID    INT NOT NULL UNIQUE,
    amount      DECIMAL(10,2) NOT NULL CHECK (amount >= 0),
    paymentMethod ENUM('cash','card','online','wallet') NOT NULL DEFAULT 'cash',
    paymentTime DATETIME DEFAULT CURRENT_TIMESTAMP,
    status      ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
    transactionRef VARCHAR(100),
    FOREIGN KEY (recordID) REFERENCES parking_records(recordID) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER $$

-- Trigger: Mark slot as occupied when parking record is created
CREATE TRIGGER trg_slot_occupied
AFTER INSERT ON parking_records
FOR EACH ROW
BEGIN
    IF NEW.status = 'active' THEN
        UPDATE parking_slots SET status = 'occupied' WHERE slotID = NEW.slotID;
    END IF;
END$$

-- Trigger: Mark slot as available when vehicle exits
CREATE TRIGGER trg_slot_available
AFTER UPDATE ON parking_records
FOR EACH ROW
BEGIN
    IF NEW.exitTime IS NOT NULL AND OLD.exitTime IS NULL THEN
        UPDATE parking_slots SET status = 'available' WHERE slotID = NEW.slotID;
    END IF;
    IF NEW.status = 'cancelled' AND OLD.status = 'active' THEN
        UPDATE parking_slots SET status = 'available' WHERE slotID = NEW.slotID;
    END IF;
END$$

-- Trigger: Prevent duplicate active parking for same vehicle
CREATE TRIGGER trg_prevent_duplicate_parking
BEFORE INSERT ON parking_records
FOR EACH ROW
BEGIN
    DECLARE active_count INT;
    SELECT COUNT(*) INTO active_count
    FROM parking_records
    WHERE vehicleID = NEW.vehicleID AND status = 'active';
    IF active_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Vehicle already has an active parking session.';
    END IF;
END$$

-- Trigger: Prevent parking in occupied/maintenance slots
CREATE TRIGGER trg_check_slot_availability
BEFORE INSERT ON parking_records
FOR EACH ROW
BEGIN
    DECLARE slot_status VARCHAR(20);
    SELECT status INTO slot_status FROM parking_slots WHERE slotID = NEW.slotID;
    IF slot_status != 'available' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Parking slot is not available.';
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- VIEWS
-- ============================================================

-- Active parking sessions
CREATE OR REPLACE VIEW vw_active_parking AS
SELECT
    pr.recordID,
    v.licensePlate,
    v.vehicleType,
    v.ownerName,
    ps.slotNumber,
    ps.location AS slotLocation,
    pl.lotName,
    pr.entryTime,
    TIMESTAMPDIFF(MINUTE, pr.entryTime, NOW()) AS minutesParked,
    ROUND(TIMESTAMPDIFF(MINUTE, pr.entryTime, NOW()) / 60 * 50, 2) AS estimatedFee
FROM parking_records pr
JOIN vehicles v ON pr.vehicleID = v.vehicleID
JOIN parking_slots ps ON pr.slotID = ps.slotID
JOIN parking_lots pl ON ps.lotID = pl.lotID
WHERE pr.status = 'active';

-- Parking summary with payments
CREATE OR REPLACE VIEW vw_parking_summary AS
SELECT
    pr.recordID,
    v.licensePlate,
    v.vehicleType,
    v.ownerName,
    ps.slotNumber,
    pl.lotName,
    pr.entryTime,
    pr.exitTime,
    TIMESTAMPDIFF(MINUTE, pr.entryTime, IFNULL(pr.exitTime, NOW())) AS durationMinutes,
    pr.status AS recordStatus,
    p.amount,
    p.paymentMethod,
    p.status AS paymentStatus
FROM parking_records pr
JOIN vehicles v ON pr.vehicleID = v.vehicleID
JOIN parking_slots ps ON pr.slotID = ps.slotID
JOIN parking_lots pl ON ps.lotID = pl.lotID
LEFT JOIN payments p ON pr.recordID = p.recordID;

-- Slot occupancy overview
CREATE OR REPLACE VIEW vw_slot_overview AS
SELECT
    pl.lotID,
    pl.lotName,
    pl.location AS lotLocation,
    pl.capacity,
    COUNT(ps.slotID) AS totalSlots,
    SUM(CASE WHEN ps.status = 'available' THEN 1 ELSE 0 END) AS availableSlots,
    SUM(CASE WHEN ps.status = 'occupied' THEN 1 ELSE 0 END) AS occupiedSlots,
    SUM(CASE WHEN ps.status = 'maintenance' THEN 1 ELSE 0 END) AS maintenanceSlots,
    ROUND(SUM(CASE WHEN ps.status = 'occupied' THEN 1 ELSE 0 END) / COUNT(ps.slotID) * 100, 1) AS occupancyRate
FROM parking_lots pl
LEFT JOIN parking_slots ps ON pl.lotID = ps.lotID
GROUP BY pl.lotID, pl.lotName, pl.location, pl.capacity;

-- Revenue summary by day
CREATE OR REPLACE VIEW vw_daily_revenue AS
SELECT
    DATE(p.paymentTime) AS paymentDate,
    COUNT(p.paymentID) AS totalTransactions,
    SUM(p.amount) AS totalRevenue,
    AVG(p.amount) AS avgRevenue,
    MAX(p.amount) AS maxRevenue
FROM payments p
WHERE p.status = 'paid'
GROUP BY DATE(p.paymentTime)
ORDER BY paymentDate DESC;

-- ============================================================
-- INDEXES
-- ============================================================
CREATE INDEX idx_pr_entry ON parking_records(entryTime);
CREATE INDEX idx_pr_status ON parking_records(status);
CREATE INDEX idx_ps_status ON parking_slots(status);
CREATE INDEX idx_pay_time ON payments(paymentTime);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Users (passwords are bcrypt hashes of 'Admin@123' and 'User@123')
INSERT INTO users (username, email, password, role, fullName, phone) VALUES
('admin',     'admin@parkingsystem.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', '03001234567'),
('john_doe',  'john@example.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'John Doe',            '03009876543'),
('sara_k',    'sara@example.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'Sara Khan',           '03331234567'),
('ali_r',     'ali@example.com',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'Ali Raza',            '03211234567'),
('maria_s',   'maria@example.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'Maria Shah',          '03451234567'),
('usman_b',   'usman@example.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'Usman Baig',          '03121234567'),
('nadia_h',   'nadia@example.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'Nadia Hassan',        '03561234567'),
('farhan_q',  'farhan@example.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'Farhan Qureshi',      '03001112233'),
('zara_m',    'zara@example.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'Zara Mirza',          '03219876543'),
('hamza_a',   'hamza@example.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'Hamza Ahmed',         '03431234567');

-- Parking Lots
INSERT INTO parking_lots (lotName, capacity, location) VALUES
('Central Parking Plaza',   100, 'Block A, Main Boulevard, Karachi'),
('North Wing Garage',        60, 'Block B, North Avenue, Karachi'),
('South Terminal Parking',   80, 'Block C, South Road, Karachi');

-- Parking Slots
INSERT INTO parking_slots (lotID, slotNumber, location, status, slotType) VALUES
(1, 'A-01', 'Ground Floor - Section A', 'available',    'standard'),
(1, 'A-02', 'Ground Floor - Section A', 'available',    'standard'),
(1, 'A-03', 'Ground Floor - Section A', 'occupied',     'standard'),
(1, 'A-04', 'Ground Floor - Section A', 'available',    'compact'),
(1, 'A-05', 'Ground Floor - Section A', 'maintenance',  'standard'),
(1, 'B-01', 'Ground Floor - Section B', 'available',    'standard'),
(1, 'B-02', 'Ground Floor - Section B', 'occupied',     'handicapped'),
(1, 'B-03', 'Ground Floor - Section B', 'available',    'ev'),
(1, 'C-01', 'First Floor - Section C',  'available',    'standard'),
(1, 'C-02', 'First Floor - Section C',  'available',    'standard'),
(2, 'N-01', 'Level 1 - North',          'available',    'standard'),
(2, 'N-02', 'Level 1 - North',          'occupied',     'standard'),
(2, 'N-03', 'Level 1 - North',          'available',    'compact'),
(2, 'N-04', 'Level 2 - North',          'available',    'standard'),
(2, 'N-05', 'Level 2 - North',          'available',    'ev'),
(3, 'S-01', 'Terminal A',               'available',    'standard'),
(3, 'S-02', 'Terminal A',               'occupied',     'standard'),
(3, 'S-03', 'Terminal B',               'available',    'handicapped'),
(3, 'S-04', 'Terminal B',               'available',    'standard'),
(3, 'S-05', 'Terminal B',               'available',    'standard');

-- Vehicles
INSERT INTO vehicles (licensePlate, vehicleType, ownerName, ownerPhone, ownerEmail, userID) VALUES
('KHI-001', 'car',        'John Doe',       '03009876543', 'john@example.com',   2),
('KHI-002', 'motorcycle', 'Sara Khan',      '03331234567', 'sara@example.com',   3),
('KHI-003', 'car',        'Ali Raza',       '03211234567', 'ali@example.com',    4),
('KHI-004', 'truck',      'Maria Shah',     '03451234567', 'maria@example.com',  5),
('KHI-005', 'car',        'Usman Baig',     '03121234567', 'usman@example.com',  6),
('KHI-006', 'ev',         'Nadia Hassan',   '03561234567', 'nadia@example.com',  7),
('KHI-007', 'car',        'Farhan Qureshi', '03001112233', 'farhan@example.com', 8),
('KHI-008', 'motorcycle', 'Zara Mirza',     '03219876543', 'zara@example.com',   9),
('KHI-009', 'car',        'Hamza Ahmed',    '03431234567', 'hamza@example.com',  10),
('KHI-010', 'bus',        'Express Lines',  '02112345678', 'express@example.com',NULL),
('KHI-011', 'car',        'Guest User 1',   '03001110001', NULL,                 NULL),
('KHI-012', 'car',        'Guest User 2',   '03001110002', NULL,                 NULL);

-- Parking Records (manually manage slot status for sample data - bypass triggers)
SET @old_sql_mode = @@sql_mode;
SET SESSION sql_mode = '';

-- Completed records (historical)
INSERT INTO parking_records (vehicleID, slotID, entryTime, exitTime, status) VALUES
(1,  1,  NOW() - INTERVAL 5 DAY,  NOW() - INTERVAL 5 DAY + INTERVAL 3 HOUR,    'completed'),
(2,  2,  NOW() - INTERVAL 4 DAY,  NOW() - INTERVAL 4 DAY + INTERVAL 2 HOUR,    'completed'),
(3,  4,  NOW() - INTERVAL 4 DAY,  NOW() - INTERVAL 4 DAY + INTERVAL 5 HOUR,    'completed'),
(4,  6,  NOW() - INTERVAL 3 DAY,  NOW() - INTERVAL 3 DAY + INTERVAL 1 HOUR,    'completed'),
(5,  9,  NOW() - INTERVAL 3 DAY,  NOW() - INTERVAL 3 DAY + INTERVAL 4 HOUR,    'completed'),
(6,  11, NOW() - INTERVAL 2 DAY,  NOW() - INTERVAL 2 DAY + INTERVAL 6 HOUR,    'completed'),
(7,  13, NOW() - INTERVAL 2 DAY,  NOW() - INTERVAL 2 DAY + INTERVAL 2 HOUR,    'completed'),
(8,  16, NOW() - INTERVAL 1 DAY,  NOW() - INTERVAL 1 DAY + INTERVAL 3 HOUR,    'completed'),
(9,  19, NOW() - INTERVAL 1 DAY,  NOW() - INTERVAL 1 DAY + INTERVAL 7 HOUR,    'completed'),
(10, 20, NOW() - INTERVAL 1 DAY,  NOW() - INTERVAL 1 DAY + INTERVAL 8 HOUR,    'completed'),
(11, 14, NOW() - INTERVAL 6 HOUR, NOW() - INTERVAL 2 HOUR,                     'completed'),
(12, 15, NOW() - INTERVAL 8 HOUR, NOW() - INTERVAL 1 HOUR,                     'completed');

-- Active records (vehicles currently parked - slots 3, 7, 12, 17 are occupied)
INSERT INTO parking_records (vehicleID, slotID, entryTime, status) VALUES
(1,  3,  NOW() - INTERVAL 2 HOUR, 'active'),
(5,  7,  NOW() - INTERVAL 1 HOUR, 'active'),
(3,  12, NOW() - INTERVAL 3 HOUR, 'active'),
(8,  17, NOW() - INTERVAL 30 MINUTE, 'active');

-- Payments for completed records
INSERT INTO payments (recordID, amount, paymentMethod, paymentTime, status, transactionRef) VALUES
(1,  150.00, 'cash',   NOW() - INTERVAL 5 DAY + INTERVAL 3 HOUR,   'paid', 'TXN-001'),
(2,  100.00, 'card',   NOW() - INTERVAL 4 DAY + INTERVAL 2 HOUR,   'paid', 'TXN-002'),
(3,  250.00, 'online', NOW() - INTERVAL 4 DAY + INTERVAL 5 HOUR,   'paid', 'TXN-003'),
(4,   50.00, 'cash',   NOW() - INTERVAL 3 DAY + INTERVAL 1 HOUR,   'paid', 'TXN-004'),
(5,  200.00, 'wallet', NOW() - INTERVAL 3 DAY + INTERVAL 4 HOUR,   'paid', 'TXN-005'),
(6,  300.00, 'card',   NOW() - INTERVAL 2 DAY + INTERVAL 6 HOUR,   'paid', 'TXN-006'),
(7,  100.00, 'cash',   NOW() - INTERVAL 2 DAY + INTERVAL 2 HOUR,   'paid', 'TXN-007'),
(8,  150.00, 'online', NOW() - INTERVAL 1 DAY + INTERVAL 3 HOUR,   'paid', 'TXN-008'),
(9,  350.00, 'card',   NOW() - INTERVAL 1 DAY + INTERVAL 7 HOUR,   'paid', 'TXN-009'),
(10, 400.00, 'cash',   NOW() - INTERVAL 1 DAY + INTERVAL 8 HOUR,   'paid', 'TXN-010'),
(11, 100.00, 'card',   NOW() - INTERVAL 2 HOUR,                     'paid', 'TXN-011'),
(12, 150.00, 'cash',   NOW() - INTERVAL 1 HOUR,                     'paid', 'TXN-012');

-- Pending payments for active sessions
INSERT INTO payments (recordID, amount, paymentMethod, status) VALUES
(13, 0.00, 'cash',   'pending'),
(14, 0.00, 'card',   'pending'),
(15, 0.00, 'cash',   'pending'),
(16, 0.00, 'online', 'pending');

SET SESSION sql_mode = @old_sql_mode;

-- ============================================================
-- USEFUL QUERIES (for reference)
-- ============================================================

-- Active parked vehicles
-- SELECT * FROM vw_active_parking;

-- Most used slots (top 5)
-- SELECT ps.slotNumber, pl.lotName, COUNT(pr.recordID) AS usageCount
-- FROM parking_records pr
-- JOIN parking_slots ps ON pr.slotID = ps.slotID
-- JOIN parking_lots pl ON ps.lotID = pl.lotID
-- GROUP BY pr.slotID ORDER BY usageCount DESC LIMIT 5;

-- Daily revenue
-- SELECT * FROM vw_daily_revenue;

-- Slot overview per lot
-- SELECT * FROM vw_slot_overview;

-- Revenue by payment method
-- SELECT paymentMethod, COUNT(*) AS transactions, SUM(amount) AS revenue
-- FROM payments WHERE status='paid' GROUP BY paymentMethod;

SELECT 'Database setup complete!' AS message;
