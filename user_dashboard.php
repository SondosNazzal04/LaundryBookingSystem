<?php
require_once 'Database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
	header("Location: login.php");
	exit;
}

$database = new Database();
$pdo = $database->getConnection();
$user_id = $_SESSION['user_id'];
$message = '';

// Cancel Reservation[cite: 1]
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_res'])) {
	$res_id = $_POST['res_id'];
	// Ensures users can only cancel their own reservations[cite: 1]
	$stmt = $pdo->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = :id AND user_id = :uid AND status IN ('Pending', 'Confirmed')");
	$stmt->execute(['id' => $res_id, 'uid' => $user_id]);
	$message = "Reservation cancelled.";
}

// Create Reservation[cite: 1]
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_slot'])) {
	$slot_id = $_POST['slot_id'];
	$note = trim($_POST['note']);

	// Check if user already booked this slot[cite: 1]
	$check = $pdo->prepare("SELECT id FROM reservations WHERE user_id = :uid AND time_slot_id = :sid AND status != 'Cancelled'");
	$check->execute(['uid' => $user_id, 'sid' => $slot_id]);

	if ($check->rowCount() > 0) {
		$message = "You have already reserved this slot.";
	} else {
		// Create new reservation with Pending status[cite: 1]
		$stmt = $pdo->prepare("INSERT INTO reservations (user_id, time_slot_id, note, status) VALUES (:uid, :sid, :note, 'Pending')");
		$stmt->execute(['uid' => $user_id, 'sid' => $slot_id, 'note' => $note]);
		$message = "Reservation submitted successfully.";
	}
}

// Fetch available future slots[cite: 1]
$slots_sql = "
	SELECT t.*,
	(t.capacity - (SELECT COUNT(*) FROM reservations r WHERE r.time_slot_id = t.id AND r.status IN ('Pending', 'Confirmed'))) AS remaining
	FROM time_slots t
	WHERE t.slot_date >= CURDATE()
	HAVING remaining > 0
";
$available_slots = $pdo->query($slots_sql)->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's own reservations[cite: 1]
$my_res_sql = "
	SELECT r.id, r.status, r.note, t.slot_date, t.start_time, t.end_time
	FROM reservations r
	JOIN time_slots t ON r.time_slot_id = t.id
	WHERE r.user_id = :uid
	ORDER BY t.slot_date, t.start_time
";
$stmt = $pdo->prepare($my_res_sql);
$stmt->execute(['uid' => $user_id]);
$my_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>User Dashboard</title>
	<link rel="stylesheet" href="./styles/styles.css">
</head>
<body>
	<nav>
		<h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
		<a href="logout.php">Logout</a>
	</nav>

	<?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>

	<h2>Available Time Slots</h2>
	<table border="1" cellpadding="10">
		<tr>
			<th>Date</th>
			<th>Time</th>
			<th>Remaining Capacity</th>
			<th>Action</th>
		</tr>
		<?php foreach ($available_slots as $slot): ?>
		<tr>
			<td><?php echo $slot['slot_date']; ?></td>
			<td><?php echo $slot['start_time'] . ' - ' . $slot['end_time']; ?></td>
			<td><?php echo $slot['remaining']; ?></td>
			<td>
				<form method="POST" style="display:inline;">
					<input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
					<input type="text" name="note" placeholder="Optional note">
					<button type="submit" name="book_slot">Reserve</button>
				</form>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>

	<h2>My Reservations</h2>
	<table border="1" cellpadding="10">
		<tr>
			<th>Date</th>
			<th>Time</th>
			<th>Note</th>
			<th>Status</th>
			<th>Action</th>
		</tr>
		<?php foreach ($my_reservations as $res): ?>
		<tr>
			<td><?php echo $res['slot_date']; ?></td>
			<td><?php echo $res['start_time'] . ' - ' . $res['end_time']; ?></td>
			<td><?php echo htmlspecialchars($res['note']); ?></td>
			<td><?php echo $res['status']; ?></td>
			<td>
				<?php if ($res['status'] == 'Pending' || $res['status'] == 'Confirmed'): ?>
				<form method="POST" style="display:inline;">
					<input type="hidden" name="res_id" value="<?php echo $res['id']; ?>">
					<button type="submit" name="cancel_res">Cancel</button>
				</form>
				<?php else: ?>
					N/A
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
</body>
</html>
