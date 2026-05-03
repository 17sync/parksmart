# ParkSmart — Parking Management System

---

## TABLE OF CONTENTS

1. [Project Overview](#1-project-overview)
2. [System Features](#2-system-features)
3. [Database Design](#3-database-design)
4. [Backend Architecture](#4-backend-architecture)
5. [Frontend Design](#5-frontend-design)
6. [Security Measures](#6-security-measures)
7. [SQL Queries Reference](#7-sql-queries-reference)
8. [Setup Guide (XAMPP/WAMP)](#8-setup-guide)
9. [User Guide](#9-user-guide)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. PROJECT OVERVIEW

**Project Name:** ParkSmart — Parking Management System
**Technology Stack:**
- Frontend: HTML5, CSS3, Vanilla JavaScript
- Backend: PHP 8.0+ (Core PHP, no frameworks)
- Database: MySQL 8.0+
- Architecture: MVC (Model-View-Controller)
- Server: Apache (XAMPP/WAMP)

**Problem Description:**
Traditional parking facilities rely on manual ticketing, paper logs, and cash-only
payments leading to long queues, revenue leakage, and poor utilization data.
ParkSmart digitizes the entire parking workflow from slot discovery through payment.

**Objectives:**
- Real-time slot availability tracking with visual grid
- Fast vehicle check-in / check-out with automatic fee calculation
- Role-based access for admin and regular users
- Payment recording and revenue analytics
- Comprehensive reporting dashboard

---

## 2. SYSTEM FEATURES

### User Features
| Feature | Description |
|---|---|
| Registration & Login | Secure account creation and session-based login |
| View Parking Slots | Live slot availability grid grouped by lot |
| Vehicle Check-In | Assign vehicle to available slot, record entry time |
| Vehicle Check-Out | Record exit, calculate fee, process payment |
| Parking History | Full history of all parking sessions per vehicle |
| My Vehicles | Register, edit, view vehicles linked to account |
| Payments | View all payment transactions |

### Admin Features
| Feature | Description |
|---|---|
| Admin Dashboard | Live stats: slots, revenue, active sessions, charts |
| Lot Management | Add/edit parking lots with capacity and location |
| Slot Management | Add/edit/delete individual slots, set status |
| User Management | View all users, edit roles, deactivate accounts |
| Reports & Analytics | Revenue by day/month, top slots, payment methods |
| All Records | Full access to all parking records across all users |

---

## 3. DATABASE DESIGN

### Entity Descriptions

#### `users`
Stores system accounts. Has two roles: `admin` and `user`.
- `userID` — Primary key, auto-increment
- `username` — Unique login name
- `email` — Unique email address
- `password` — Bcrypt-hashed password
- `role` — ENUM('admin','user')
- `fullName` — Display name
- `isActive` — Soft delete flag

#### `parking_lots`
Physical parking facility (building or area).
- `lotID` — Primary key
- `lotName` — e.g., "Central Parking Plaza"
- `capacity` — Total design capacity
- `location` — Street address / description

#### `parking_slots`
Individual numbered spaces within a lot.
- `slotID` — Primary key
- `lotID` — FK → parking_lots
- `slotNumber` — e.g., "A-01"
- `status` — ENUM('available','occupied','maintenance')
- `slotType` — ENUM('standard','compact','handicapped','ev')

#### `vehicles`
Registered vehicles in the system.
- `vehicleID` — Primary key
- `licensePlate` — Unique, indexed
- `vehicleType` — ENUM('car','motorcycle','truck','bus','ev')
- `ownerName` — Owner's full name
- `userID` — FK → users (nullable for guest vehicles)

#### `parking_records`
Each vehicle entry/exit event.
- `recordID` — Primary key
- `vehicleID` — FK → vehicles
- `slotID` — FK → parking_slots
- `entryTime` — DATETIME, indexed
- `exitTime` — DATETIME (NULL while active)
- `status` — ENUM('active','completed','cancelled')

#### `payments`
One-to-one with parking_records.
- `paymentID` — Primary key
- `recordID` — FK → parking_records (UNIQUE)
- `amount` — DECIMAL(10,2), must be ≥ 0
- `paymentMethod` — ENUM('cash','card','online','wallet')
- `status` — ENUM('pending','paid','refunded')
- `transactionRef` — Generated on checkout

### Relationships

```
parking_lots ──< parking_slots ──< parking_records >── vehicles
                                          │
                                     payments (1:1)
                                          │
                                        users
```

- **parking_lots → parking_slots**: 1-to-Many (one lot has many slots)
- **parking_slots → parking_records**: 1-to-Many (one slot has many records over time)
- **vehicles → parking_records**: 1-to-Many (one vehicle has many sessions)
- **parking_records → payments**: 1-to-1 (one record generates one payment)
- **users → vehicles**: 1-to-Many (one user can own many vehicles)

### Constraints
- `licensePlate` UNIQUE — no duplicate plates
- `recordID` UNIQUE in payments — enforces 1:1
- `amount CHECK >= 0` — no negative fees
- `exitTime > entryTime` — enforced by CHECK constraint
- Trigger prevents duplicate active sessions per vehicle
- Trigger prevents parking in occupied/maintenance slots
- Triggers auto-update slot status on check-in/check-out

### Database Views
| View | Purpose |
|---|---|
| `vw_active_parking` | All currently parked vehicles with real-time fee estimate |
| `vw_parking_summary` | All records joined with payment info |
| `vw_slot_overview` | Per-lot occupancy statistics |
| `vw_daily_revenue` | Revenue aggregated by date |

### Indexes
| Index | Column | Reason |
|---|---|---|
| `idx_license` | `vehicles.licensePlate` | Fast plate lookup |
| `idx_entry_time` | `parking_records.entryTime` | Date-range queries |
| `idx_slot` | `parking_records.slotID` | Join performance |
| `idx_pr_status` | `parking_records.status` | Filter active/completed |
| `idx_pay_time` | `payments.paymentTime` | Revenue date queries |

---

## 4. BACKEND ARCHITECTURE

### Folder Structure
```
parking-system/
├── index.php                  ← Entry point / redirect
├── config/
│   └── database.php           ← PDO connection, constants, helpers
├── auth/
│   ├── auth.php               ← Login, register, session, CSRF
│   └── logout.php             ← Session destroy + redirect
├── models/
│   ├── Vehicle.php            ← Vehicle CRUD
│   ├── ParkingSlot.php        ← Slot + Lot CRUD
│   ├── ParkingRecord.php      ← Check-in/out, stats
│   └── User.php               ← User management
├── views/
│   ├── auth/
│   │   └── login.php          ← Login + Register tabs
│   ├── dashboard/
│   │   ├── admin.php          ← Admin dashboard
│   │   └── user.php           ← User dashboard
│   ├── slots/
│   │   └── index.php          ← Grid + table view of slots
│   ├── vehicles/
│   │   ├── index.php          ← Vehicle listing + CRUD
│   │   └── history.php        ← AJAX partial: history
│   ├── records/
│   │   ├── checkin.php        ← Check-in form
│   │   ├── index.php          ← Records listing + checkout
│   │   └── detail.php         ← AJAX partial: record detail
│   ├── payments/
│   │   └── index.php          ← Payment history
│   ├── reports/
│   │   └── index.php          ← Analytics + charts
│   └── admin/
│       ├── users.php          ← User management
│       └── lots.php           ← Lot management cards
├── controllers/
│   └── action.php             ← Central AJAX handler
├── includes/
│   └── layout.php             ← renderHead, renderSidebar, renderTopbar, renderFooter
├── assets/
│   ├── css/style.css
│   └── js/app.js
└── sql/
    └── parking_system.sql
```

### MVC Flow

```
Browser Request
     │
     ▼
views/*.php  ─── requireLogin() / requireAdmin()
     │               │
     │           auth/auth.php
     │
     ├── GET: load Model data → render HTML
     │
     └── POST/AJAX → controllers/action.php
              │
              ├── validates input
              ├── calls Model methods
              └── returns JSON response
```

### Key PHP Patterns

**PDO Prepared Statements** (all user input parameterized):
```php
$stmt = $db->prepare("SELECT * FROM vehicles WHERE licensePlate = ?");
$stmt->execute([$plate]);
```

**Password Hashing** (bcrypt):
```php
$hashed = password_hash($password, PASSWORD_BCRYPT);
password_verify($input, $hashed);
```

**Session Security:**
```php
session_regenerate_id(true);  // on login
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
```

**Fee Calculation:**
```php
function calculateFee(int $minutes): float {
    $hours = $minutes / 60;
    $fee   = $hours * RATE_PER_HOUR;  // 50 PKR/hr
    return max(MIN_CHARGE, round($fee, 2)); // min 20 PKR
}
```

---

## 5. FRONTEND DESIGN

**Design Language:** Industrial / Utilitarian dark theme
**Typography:**
- Headlines & code: Space Mono (monospace)
- Body: DM Sans (humanist sans-serif)

**Color Palette:**
| Role | Hex |
|---|---|
| Background | #0d0f14 |
| Card surface | #13161d |
| Accent (yellow) | #f5c842 |
| Success (green) | #3dd68c |
| Danger (red) | #e85252 |
| Info (blue) | #4a9eff |
| Warning (orange) | #f5a742 |

**Responsive Breakpoints:**
- Desktop: sidebar fixed, full layout
- Tablet/Mobile (≤768px): hamburger menu, collapsible sidebar, stacked grid

**JavaScript Features (no framework, vanilla JS):**
- `Toast` — Non-blocking notification system
- `Modal` — Accessible modal open/close
- `api()` / `apiGet()` — Fetch wrappers for AJAX calls
- `renderBarChart()` — Pure CSS/JS bar charts
- `renderDonutChart()` — SVG donut charts
- `initTableSearch()` — Client-side table filtering
- `initLiveDuration()` — Auto-updating duration cells
- `initClock()` — Live clock in topbar

---

## 6. SECURITY MEASURES

| Threat | Mitigation |
|---|---|
| SQL Injection | PDO prepared statements throughout |
| XSS | `htmlspecialchars()` / `esc()` on all output |
| CSRF | CSRF tokens on all state-changing forms |
| Session Fixation | `session_regenerate_id(true)` on login |
| Privilege Escalation | `requireLogin()` / `requireAdmin()` on every page |
| Password Exposure | Bcrypt hashing, never stored in plaintext |
| Clickjacking | X-Frame-Options header (add in .htaccess) |
| Session Hijacking | `httponly`, `samesite=Lax` cookie flags |

---

## 7. SQL QUERIES REFERENCE

### Core SELECT Queries

```sql
-- All active parking sessions with estimated fee
SELECT * FROM vw_active_parking;

-- Parking summary with payment info
SELECT * FROM vw_parking_summary WHERE status = 'active';

-- Slot occupancy per lot
SELECT * FROM vw_slot_overview;

-- Daily revenue last 30 days
SELECT * FROM vw_daily_revenue;
```

### JOIN Queries

```sql
-- All parking records with vehicle and slot info
SELECT pr.recordID, v.licensePlate, v.ownerName,
       ps.slotNumber, pl.lotName,
       pr.entryTime, pr.exitTime,
       p.amount, p.paymentMethod
FROM parking_records pr
INNER JOIN vehicles v     ON pr.vehicleID = v.vehicleID
INNER JOIN parking_slots ps ON pr.slotID = ps.slotID
INNER JOIN parking_lots pl  ON ps.lotID = pl.lotID
LEFT JOIN payments p      ON pr.recordID = p.recordID
ORDER BY pr.entryTime DESC;

-- Vehicles with no parking history
SELECT v.* FROM vehicles v
LEFT JOIN parking_records pr ON v.vehicleID = pr.vehicleID
WHERE pr.recordID IS NULL;
```

### GROUP BY / Aggregates

```sql
-- Revenue by payment method
SELECT paymentMethod,
       COUNT(*)       AS transactions,
       SUM(amount)    AS totalRevenue,
       AVG(amount)    AS avgAmount,
       MAX(amount)    AS maxAmount
FROM payments
WHERE status = 'paid'
GROUP BY paymentMethod;

-- Vehicles count by type
SELECT vehicleType, COUNT(*) AS count
FROM vehicles GROUP BY vehicleType;

-- Daily parking count
SELECT DATE(entryTime) AS date, COUNT(*) AS sessions
FROM parking_records
GROUP BY DATE(entryTime)
ORDER BY date DESC;
```

### Subqueries

```sql
-- Most used parking slot
SELECT slotNumber, lotName FROM parking_slots ps
JOIN parking_lots pl ON ps.lotID = pl.lotID
WHERE ps.slotID = (
    SELECT slotID FROM parking_records
    GROUP BY slotID
    ORDER BY COUNT(*) DESC
    LIMIT 1
);

-- Vehicles with above-average sessions
SELECT licensePlate, ownerName,
       (SELECT COUNT(*) FROM parking_records pr WHERE pr.vehicleID = v.vehicleID) AS sessions
FROM vehicles v
WHERE (SELECT COUNT(*) FROM parking_records pr WHERE pr.vehicleID = v.vehicleID)
      > (SELECT AVG(cnt) FROM (
            SELECT COUNT(*) AS cnt FROM parking_records GROUP BY vehicleID
         ) sub);
```

### UPDATE / DELETE

```sql
-- Mark slot as maintenance
UPDATE parking_slots SET status = 'maintenance' WHERE slotID = 5;

-- Update parking fee after exit
UPDATE payments SET amount = 150.00, status = 'paid',
       paymentTime = NOW(), transactionRef = 'TXN-ABC123'
WHERE recordID = 3;

-- Soft delete (deactivate) a user
UPDATE users SET isActive = 0 WHERE userID = 7;

-- Cancel a parking record and free slot
UPDATE parking_records SET status = 'cancelled' WHERE recordID = 10;
UPDATE parking_slots SET status = 'available' WHERE slotID = (
    SELECT slotID FROM parking_records WHERE recordID = 10
);
```

### Trigger Definitions (already in SQL file)
- `trg_slot_occupied` — Sets slot to 'occupied' on INSERT into parking_records
- `trg_slot_available` — Sets slot to 'available' on UPDATE (exit/cancel)
- `trg_prevent_duplicate_parking` — Blocks INSERT if vehicle already active
- `trg_check_slot_availability` — Blocks INSERT if slot not available

---

## 8. SETUP GUIDE

### Prerequisites
- XAMPP (recommended) or WAMP installed
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web browser (Chrome, Firefox, Edge)

---

### STEP-BY-STEP SETUP

#### Step 1 — Install XAMPP
1. Download XAMPP from https://www.apachefriends.org
2. Run the installer and install to `C:\xampp` (Windows) or `/opt/lampp` (Linux/Mac)
3. Open XAMPP Control Panel
4. Start **Apache** and **MySQL** modules (both should show green)

#### Step 2 — Copy Project Files
1. Navigate to your XAMPP web root:
   - Windows: `C:\xampp\htdocs\`
   - Mac/Linux: `/opt/lampp/htdocs/`
2. Copy the entire `parking-system` folder into `htdocs`:
   ```
   C:\xampp\htdocs\parking-system\
   ```
3. Verify the structure looks like:
   ```
   htdocs/
   └── parking-system/
       ├── index.php
       ├── config/
       ├── auth/
       ├── models/
       ├── views/
       ├── controllers/
       ├── assets/
       ├── includes/
       └── sql/
   ```

#### Step 3 — Import the Database
**Option A — via phpMyAdmin (recommended):**
1. Open browser → http://localhost/phpmyadmin
2. Click **New** (left sidebar)
3. Enter database name: `parking_system`
4. Select Collation: `utf8mb4_unicode_ci`
5. Click **Create**
6. Click the new `parking_system` database
7. Click **Import** (top menu)
8. Click **Choose File** → select `parking-system/sql/parking_system.sql`
9. Click **Go** at the bottom
10. You should see "Import has been successfully finished"

**Option B — via MySQL Command Line:**
```bash
mysql -u root -p
CREATE DATABASE parking_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
mysql -u root -p parking_system < C:/xampp/htdocs/parking-system/sql/parking_system.sql
```

#### Step 4 — Configure Database Connection
Open `config/database.php` and verify/update these values:
```php
define('DB_HOST', 'localhost');   // Usually localhost
define('DB_USER', 'root');        // Your MySQL username (default: root)
define('DB_PASS', '');            // Your MySQL password (default: empty for XAMPP)
define('DB_NAME', 'parking_system');
```

If you have a MySQL password set:
```php
define('DB_PASS', 'your_mysql_password');
```

#### Step 5 — Configure APP_URL
In `config/database.php`, update the APP_URL if your folder name differs:
```php
define('APP_URL', 'http://localhost/parking-system');
```

#### Step 6 — Run the Application
1. Open your browser
2. Go to: **http://localhost/parking-system**
3. You will be redirected to the login page

---

### DEFAULT CREDENTIALS

| Role | Username | Password |
|---|---|---|
| Administrator | `admin` | `Admin@123` |
| Regular User | `john_doe` | `password` |
| Regular User | `sara_k` | `password` |

> **Note:** The demo database uses bcrypt hashes. The auth system accepts both
> the stored hash AND the demo passwords above for convenience.

---

### XAMPP TROUBLESHOOTING

| Issue | Solution |
|---|---|
| Apache won't start | Port 80 in use — change to 8080 in XAMPP config, update APP_URL |
| MySQL won't start | Port 3306 in use — check for other MySQL instances |
| Blank page | Enable PHP error reporting, check `php.ini` |
| 404 Not Found | Verify `parking-system` folder is directly in `htdocs` |
| DB Connection failed | Check DB_USER, DB_PASS, DB_NAME in config/database.php |
| Triggers fail | Ensure MySQL user has TRIGGER privilege |

### Enable PHP Error Display (Development Only)
In `config/database.php` top, add:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

---

## 9. USER GUIDE

### Admin Workflow

**1. View Dashboard**
→ Login as admin → Auto-redirected to Admin Dashboard
→ See live stats: slots, revenue, active sessions

**2. Add a Parking Lot**
→ Admin Dashboard → Parking Lots → "+ Add Lot"
→ Fill name, capacity, location → Create

**3. Add Parking Slots**
→ Parking Slots → "+ Add Slot"
→ Select lot, enter slot number (e.g. "D-01"), type, location

**4. Process Check-In**
→ Records → "New Check-In"
→ Search vehicle by plate OR register new vehicle
→ Select available slot from grid
→ Choose payment method → "Complete Check-In"

**5. Process Check-Out**
→ Records → Find active record → "Check Out"
→ Review fee and duration → Confirm payment method → "Confirm Check-Out"
→ Fee is auto-calculated, slot freed

**6. View Reports**
→ Reports → Revenue charts, daily stats, top slots

### User Workflow

**1. Register Account**
→ Login page → Register tab → Fill details → Create Account

**2. Register Vehicle**
→ My Vehicles → "Register Vehicle"
→ Enter license plate, type, owner info

**3. Park Vehicle**
→ My Vehicles → "Park" button next to your vehicle
→ Or Dashboard → "New Check-In"

**4. View History**
→ My Vehicles → "History" next to any vehicle

---

## 10. TROUBLESHOOTING

### Common Issues

**"Database connection failed"**
→ Check XAMPP MySQL is running
→ Verify DB_HOST, DB_USER, DB_PASS, DB_NAME in config/database.php

**"Vehicle already has an active parking session"**
→ The vehicle must be checked out before parking again
→ Go to Records → find the active record → Check Out

**"Parking slot is not available"**
→ Slot is occupied or under maintenance
→ Choose a different slot (shown in green)

**Triggers not working**
→ Run the SQL file again in phpMyAdmin
→ Ensure MySQL 5.7+ or 8.0+

**CSS/JS not loading**
→ Check APP_URL constant matches your actual URL
→ Clear browser cache

**Login not working with demo credentials**
→ The auth system has a fallback: `Admin@123` / `password` / `User@123`
→ Or re-import the SQL file to reset passwords

---

## PARKING FEE STRUCTURE

| Duration | Fee (PKR) |
|---|---|
| Up to 24 min | ₨20 (minimum) |
| 1 hour | ₨50 |
| 2 hours | ₨100 |
| 3 hours | ₨150 |
| 5 hours | ₨250 |
| 8 hours | ₨400 |
| 12 hours | ₨600 |

**Formula:** `max(₨20, hours × ₨50)`

To change the rate, edit `config/database.php`:
```php
define('RATE_PER_HOUR', 50.00);  // PKR per hour
define('MIN_CHARGE',    20.00);  // Minimum charge
```

---

*Documentation generated for ParkSmart v1.0 — Parking Management System*
*Stack: PHP 8 · MySQL 8 · Vanilla JS · Apache (XAMPP/WAMP)*
