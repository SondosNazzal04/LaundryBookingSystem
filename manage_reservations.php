<?php
require_once 'Database.php';
session_start();

// Authorization: Protect admin routes on the server[cite: 1]
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: login.php");
	exit;
}

$database = new Database();
$pdo = $database->getConnection();
$message = '';

// --- 1. HANDLE POST REQUESTS (CRUD OPERATIONS) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

	// Create Time Slot[cite: 1]
	if (isset($_POST['add_slot'])) {
		$date = $_POST['slot_date'];
		$start = $_POST['start_time'];
		$end = $_POST['end_time'];
		$capacity = (int)$_POST['capacity'];

		$stmt = $pdo->prepare("INSERT INTO time_slots (slot_date, start_time, end_time, capacity) VALUES (?, ?, ?, ?)");
		if ($stmt->execute([$date, $start, $end, $capacity])) {
			$message = "<div style='color:green; font-weight:bold;'>Time slot created successfully! It is now available to users.</div>";
		}
	}

	// Delete Time Slot[cite: 1]
	elseif (isset($_POST['delete_slot'])) {
		$slot_id = $_POST['slot_id'];

		// Business Rule: Time slots with pending or confirmed reservations cannot be deleted[cite: 1]
		$check = $pdo->prepare("SELECT id FROM reservations WHERE time_slot_id = ? AND status IN ('Pending', 'Confirmed')");
		$check->execute([$slot_id]);

		if ($check->rowCount() > 0) {
			$message = "<div style='color:red; font-weight:bold;'>Error: Cannot delete slot. It has pending or confirmed reservations.</div>";
		} else {
			$stmt = $pdo->prepare("DELETE FROM time_slots WHERE id = ?");
			$stmt->execute([$slot_id]);
			$message = "<div style='color:green; font-weight:bold;'>Time slot deleted successfully.</div>";
		}
	}

	// Update Reservation Status (Confirm, Reject, Cancel)[cite: 1]
	elseif (isset($_POST['update_res_status'])) {
		$res_id = $_POST['res_id'];
		$new_status = $_POST['new_status'];

		$stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
		if ($stmt->execute([$new_status, $res_id])) {
			$message = "<div style='color:green; font-weight:bold;'>Reservation status updated to $new_status.</div>";
		}
	}
}

// --- 2. FETCH DATA FOR DISPLAY ---

// Fetch all time slots
$slots = $pdo->query("SELECT * FROM time_slots ORDER BY slot_date DESC, start_time DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch reservations with Search & Filter[cite: 1]
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$query = "SELECT r.id, r.status, r.note, u.name, u.email, t.slot_date, t.start_time, t.end_time
		FROM reservations r
		JOIN users u ON r.user_id = u.id
		JOIN time_slots t ON r.time_slot_id = t.id
		WHERE 1=1";
$params = [];

if ($search) {
	$query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
	$params[] = "%$search%";
	$params[] = "%$search%";
}
if ($status_filter) {
	$query .= " AND r.status = ?";
	$params[] = $status_filter;
}

$query .= " ORDER BY t.slot_date DESC, t.start_time DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Manage Reservations & Slots</title>
	<link rel="stylesheet" href="./styles/styles.css">
	<style>
		.section { border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; }
		table { width: 100%; border-collapse: collapse; margin-top: 10px; }
		th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
		th { background-color: #f2f2f2; }
	</style>
</head>
<body>
	<nav>
		<a href="admin_dashboard.php">Back to Dashboard</a> |
		<a href="manage_users.php">Manage Users</a> |
		<a href="logout.php">Logout</a>
	</nav>

	<h2>Manage System Operations</h2>
	<?= $message ?>

	<!-- SECTION 1: MANAGE TIME SLOTS -->
	<div class="section">
		<h3>1. Create New Time Slot</h3>
		<form method="POST">
			<label>Date:</label> <input type="date" name="slot_date" required>
			<label>Start:</label> <input type="time" name="start_time" required>
			<label>End:</label> <input type="time" name="end_time" required>
			<label>Capacity:</label> <input type="number" name="capacity" min="1" required>
			<button type="submit" name="add_slot">Add Time Slot</button>
		</form>

		<h3>Existing Time Slots</h3>
		<table>
			<tr><th>ID</th><th>Date</th><th>Time</th><th>Capacity</th><th>Action</th></tr>
			<?php foreach ($slots as $slot): ?>
			<tr>
				<td><?= $slot['id'] ?></td>
				<td><?= $slot['slot_date'] ?></td>
				<td><?= $slot['start_time'] ?> - <?= $slot['end_time'] ?></td>
				<td><?= $slot['capacity'] ?></td>
				<td>
					<form method="POST" style="display:inline;">
						<input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
						<button type="submit" name="delete_slot" onclick="return confirm('Delete this slot?')">Delete</button>
					</form>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<!-- SECTION 2: MANAGE RESERVATIONS -->
	<div class="section">
		<h3>2. Manage User Reservations</h3>

		<!-- Search and Filter Form -->
		<form method="GET" style="margin-bottom: 10px;">
			<input type="text" name="search" placeholder="Search Name or Email" value="<?= htmlspecialchars($search) ?>">
			<select name="status">
				<option value="">All Statuses</option>
				<option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
				<option value="Confirmed" <?= $status_filter == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
				<option value="Rejected" <?= $status_filter == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
				<option value="Cancelled" <?= $status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
			</select>
			<button type="submit">Filter/Search</button>
			<a href="manage_reservations.php"><button type="button">Reset</button></a>
		</form>

		<!-- Reservations Table -->
		<table>
			<tr>
				<th>ID</th>
				<th>User</th>
				<th>Email</th>
				<th>Date & Time</th>
				<th>Note</th>
				<th>Status</th>
				<th>Change Status</th>
			</tr>
			<?php foreach ($reservations as $res): ?>
			<tr>
				<td><?= $res['id'] ?></td>
				<td><?= htmlspecialchars($res['name']) ?></td>
				<td><?= htmlspecialchars($res['email']) ?></td>
				<td><?= $res['slot_date'] ?> (<?= $res['start_time'] ?>)</td>
				<td><?= htmlspecialchars($res['note']) ?></td>
				<td><strong><?= $res['status'] ?></strong></td>
				<td>
					<form method="POST" style="display:inline;">
						<input type="hidden" name="res_id" value="<?= $res['id'] ?>">
						<select name="new_status">
							<option value="Pending" <?= $res['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
							<option value="Confirmed" <?= $res['status'] == 'Confirmed' ? 'selected' : '' ?>>Confirm</option>
							<option value="Rejected" <?= $res['status'] == 'Rejected' ? 'selected' : '' ?>>Reject</option>
							<option value="Cancelled" <?= $res['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancel</option>
						</select>
						<button type="submit" name="update_res_status">Update</button>
					</form>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>
</body>
</html>
