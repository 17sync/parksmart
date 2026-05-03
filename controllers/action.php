<?php
// ============================================================
// controllers/action.php - Central action handler (AJAX/POST)
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../models/Vehicle.php';
require_once __DIR__ . '/../models/ParkingSlot.php';
require_once __DIR__ . '/../models/ParkingRecord.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    // ---- AUTH ----
    case 'login':
        $result = loginUser(
            trim($_POST['username'] ?? ''),
            $_POST['password'] ?? ''
        );
        echo json_encode($result);
        break;

    case 'register':
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'email'    => trim($_POST['email']    ?? ''),
            'password' => $_POST['password']      ?? '',
            'fullName' => trim($_POST['fullName'] ?? ''),
            'phone'    => trim($_POST['phone']    ?? ''),
        ];
        // Basic validation
        if (!$data['username'] || !$data['email'] || !$data['password'] || !$data['fullName']) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
            break;
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            break;
        }
        if (strlen($data['password']) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            break;
        }
        echo json_encode(registerUser($data));
        break;

    case 'logout':
        logoutUser();
        break;

    // ---- VEHICLES ----
    case 'vehicle_create':
        requireLogin();
        $v = new Vehicle();
        $plate = strtoupper(trim($_POST['licensePlate'] ?? ''));
        if (!$plate) { echo json_encode(['success'=>false,'message'=>'License plate required.']); break; }
        if ($v->getByLicense($plate)) { echo json_encode(['success'=>false,'message'=>'License plate already registered.']); break; }
        $id = $v->create($_POST + ['userID' => currentUser()['id']]);
        echo json_encode(['success'=>true,'message'=>'Vehicle registered successfully.','vehicleID'=>$id]);
        break;

    case 'vehicle_update':
        requireLogin();
        $v = new Vehicle();
        $id = (int)($_POST['vehicleID'] ?? 0);
        $ok = $v->update($id, $_POST);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Vehicle updated.':'Update failed.']);
        break;

    case 'vehicle_delete':
        requireAdmin();
        $v = new Vehicle();
        $id = (int)($_POST['vehicleID'] ?? 0);
        $ok = $v->delete($id);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Vehicle deleted.':'Cannot delete vehicle with active session.']);
        break;

    case 'vehicle_search':
        requireLogin();
        $v = new Vehicle();
        $q = trim($_GET['q'] ?? '');
        if (!$q) { echo json_encode(['success'=>false,'message'=>'Enter a license plate to search.']); break; }
        $found = $v->getByLicense($q);
        if (!$found) { echo json_encode(['success'=>false,'message'=>'No vehicle found with that plate.']); break; }
        echo json_encode(['success'=>true,'vehicle'=>$found]);
        break;

    // ---- SLOTS ----
    case 'slot_create':
        requireAdmin();
        $ps = new ParkingSlot();
        $id = $ps->create($_POST);
        echo json_encode(['success'=>true,'message'=>'Slot created successfully.','slotID'=>$id]);
        break;

    case 'slot_update':
        requireAdmin();
        $ps = new ParkingSlot();
        $id = (int)($_POST['slotID'] ?? 0);
        $ok = $ps->update($id, $_POST);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Slot updated.':'Update failed.']);
        break;

    case 'slot_delete':
        requireAdmin();
        $ps = new ParkingSlot();
        $id = (int)($_POST['slotID'] ?? 0);
        $ok = $ps->delete($id);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Slot deleted.':'Cannot delete slot with active session.']);
        break;

    case 'slot_status':
        requireAdmin();
        $ps = new ParkingSlot();
        $id = (int)($_POST['slotID'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['available','occupied','maintenance'])) {
            echo json_encode(['success'=>false,'message'=>'Invalid status.']); break;
        }
        $ok = $ps->updateStatus($id, $status);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Status updated.':'Update failed.']);
        break;

    case 'lot_create':
        requireAdmin();
        $ps = new ParkingSlot();
        $id = $ps->createLot($_POST);
        echo json_encode(['success'=>true,'message'=>'Parking lot created.','lotID'=>$id]);
        break;

    // ---- PARKING OPERATIONS ----
    case 'checkin':
        requireLogin();
        $pr = new ParkingRecord();
        $vehicleID = (int)($_POST['vehicleID'] ?? 0);
        $slotID    = (int)($_POST['slotID']    ?? 0);
        $method    = $_POST['paymentMethod'] ?? 'cash';
        if (!$vehicleID || !$slotID) {
            echo json_encode(['success'=>false,'message'=>'Vehicle and slot are required.']); break;
        }
        echo json_encode($pr->checkIn($vehicleID, $slotID, $method));
        break;

    case 'checkout':
        requireLogin();
        $pr = new ParkingRecord();
        $recordID = (int)($_POST['recordID'] ?? 0);
        $method   = $_POST['paymentMethod'] ?? 'cash';
        if (!$recordID) { echo json_encode(['success'=>false,'message'=>'Record ID required.']); break; }
        echo json_encode($pr->checkOut($recordID, $method));
        break;

    // ---- REPORTS / DATA (GET) ----
    case 'get_active':
        requireLogin();
        $pr = new ParkingRecord();
        echo json_encode(['success'=>true,'data'=>$pr->getActive()]);
        break;

    case 'get_stats':
        requireLogin();
        $pr = new ParkingRecord();
        echo json_encode(['success'=>true,'data'=>$pr->getDashboardStats()]);
        break;

    case 'get_available_slots':
        requireLogin();
        $ps = new ParkingSlot();
        echo json_encode(['success'=>true,'data'=>$ps->getAvailable()]);
        break;

    // ---- USERS ----
    case 'user_update':
        requireAdmin();
        $u = new User();
        $id = (int)($_POST['userID'] ?? 0);
        $ok = $u->update($id, $_POST);
        echo json_encode(['success'=>$ok,'message'=>$ok?'User updated.':'Update failed.']);
        break;

    case 'user_delete':
        requireAdmin();
        $u = new User();
        $id = (int)($_POST['userID'] ?? 0);
        $ok = $u->delete($id);
        echo json_encode(['success'=>$ok,'message'=>$ok?'User deactivated.':'Deactivation failed.']);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Unknown action.']);
}
