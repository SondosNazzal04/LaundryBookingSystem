<?php

require_once 'Database.php';
require_once 'User.php';

if ($_SERVER["REQUEST_METHOD"] == 'POST') {
	$database = new Database();
	$pdo = $database->getConnection();
	$message = '';

	if (isset($_POST['register-btn'])) {
		try {
			$userObj = new User($pdo, trim($_POST['name']), trim($_POST['email']), $_POST['password']);
			$userObj->register();
			$message = 'User Registerd Succefully';
		} catch (\Throwable $th) {
			$message = $th->getMessage();
		}
	}

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sign up</title>
	<link rel="stylesheet" href="./styles/styles.css">
</head>
<body>
	<form action="" method="POST">
		<label for="name">Name</label>
		<input type="text" id="name" name="name" requierd>
		<label for="email">Email</label>
		<input type="text" id="email" name="email" required>
		<label for="password">Password</label>
		<input type="password" name="password" id="password" required>
		<button type="submit" name="register-btn">Create Account</button>
	</form>
</body>
</html>
