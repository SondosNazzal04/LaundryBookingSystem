<?php
require_once 'Database.php';
require_once 'User.php';

session_start();
if (isset($_SESSION['user_id'])) {
	// Redirect if already logged in
	header("Location: " . ($_SESSION['role'] === 'admin' ? "admin_dashboard.php" : "user_dashboard.php"));
	exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == 'POST' && isset($_POST['login-btn'])) {
	$database = new Database();
	$pdo = $database->getConnection();

	try {
		$userObj = new User($pdo);
		$role = $userObj->login(trim($_POST['email']), $_POST['password']);

		if ($role === 'admin') {
			header("Location: admin_dashboard.php");
		} else {
			header("Location: user_dashboard.php");
		}
		exit;
	} catch (Exception $e) {
		$message = $e->getMessage();
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login</title>
	<link rel="stylesheet" href="./styles/styles.css">
</head>
<body>
	<h2>Login</h2>
	<?php if ($message): ?>
		<p style="color: red;"><?php echo htmlspecialchars($message); ?></p>
	<?php endif; ?>
	<form action="" method="POST">
		<label for="email">Email</label>
		<input type="text" id="email" name="email" required>

		<label for="password">Password</label>
		<input type="password" name="password" id="password" required>

		<button type="submit" name="login-btn">Log In</button>
	</form>
</body>
</html>
