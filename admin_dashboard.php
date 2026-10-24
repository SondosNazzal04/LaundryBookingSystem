<?php
require_once 'Database.php';
session_start();

// Authorization: Protect admin routes on the server
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: login.php");
	exit;
}

$database = new Database();
$pdo = $database->getConnection();

// Fetch Dashboard Statistics
$stats = [
	'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
	'total_res' => $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
	'pending' => $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'Pending'")->fetchColumn(),
	'confirmed' => $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'Confirmed'")->fetchColumn(),
	'rejected' => $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'Rejected'")->fetchColumn(),
	'cancelled' => $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'Cancelled'")->fetchColumn(),
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin Dashboard</title>
	<link rel="stylesheet" href="./styles/styles.css">
	<!-- Chart.js for Reservation Status Chart -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
	<nav>
		<h1>Admin Dashboard</h1>
		<p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
		<a href="login.php">Logout</a>
	</nav>

	<div class="dashboard-stats">
		<div class="stat-card">Registered Users: <?php echo $stats['users']; ?></div>
		<div class="stat-card">Total Reservations: <?php echo $stats['total_res']; ?></div>
		<div class="stat-card">Pending: <?php echo $stats['pending']; ?></div>
		<div class="stat-card">Confirmed: <?php echo $stats['confirmed']; ?></div>
	</div>

	<!-- Chart.js Canvas -->
	<div style="width: 50%; margin: auto;">
		<canvas id="statusChart"></canvas>
	</div>

	<script>
		const ctx = document.getElementById('statusChart').getContext('2d');
		new Chart(ctx, {
			type: 'pie',
			data: {
				labels: ['Pending', 'Confirmed', 'Rejected', 'Cancelled'],
				datasets: [{
					label: 'Reservations by Status',
					data: [
						<?php echo $stats['pending']; ?>,
						<?php echo $stats['confirmed']; ?>,
						<?php echo $stats['rejected']; ?>,
						<?php echo $stats['cancelled']; ?>
					],
					backgroundColor: ['#f6c23e', '#1cc88a', '#e74a3b', '#858796']
				}]
			}
		});
	</script>
</body>
</html>
