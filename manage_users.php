<?php
require_once 'Database.php';
require_once 'User.php';
session_start();

// Authorization: Protect admin routes on the server[cite: 1]
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: login.php");
	exit;
}

$message = '';
$database = new Database();
$pdo = $database->getConnection();
// Instantiate with empty parameters since we are just using the helper methods
$userObj = new User($pdo);

if($_SERVER["REQUEST_METHOD"] == "POST"){

	// --- 1. CREATE USER LOGIC ---
	if (isset($_POST['btn_create'])) {
		$name = trim($_POST['name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$password = $_POST['password'] ?? '';

		if(empty($name) || empty($email) || empty($password)) {
			$message = "<div style='color:red;'>Please fill all inputs</div>";
		} else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$message = "<div style='color:red;'>Please use a VALID EMAIL</div>";
		} else {
			if($userObj->emailExists($email)) {
				$message = "<div style='color:red;'>This email is used before</div>";
			} else {
				$newUser = new User($pdo, $name, $email, $password);
				if($newUser->register()) {
					$message = "<div style='color:green;'>Great Job! User created.</div>";
				} else {
					$message = "<div style='color:red;'>Database Error: Could not insert user.</div>";
				}
			}
		}
	}

	// --- 2. EDIT USER LOGIC ---
	else if (isset($_POST['edit-user-id'])) {
		$editName = trim($_POST['edit-name'] ?? '');
		$editEmail = trim($_POST['edit-email'] ?? '');
		$editPassword = trim($_POST['edit-password'] ?? '');
		$editUserId = $_POST['edit-user-id'];

		if(empty($editName) || empty($editEmail) || empty($editPassword)) {
			$message = "<div style='color:red;'>Please fill all edit inputs at the top before clicking edit.</div>";
		} elseif(!filter_var($editEmail, FILTER_VALIDATE_EMAIL)) {
			$message = "<div style='color:red;'>Please fill VALID EMAIL</div>";
		} else {
			if ($userObj->emailExists($editEmail, $editUserId)) {
				$message = "<div style='color:red;'>Email is already taken by another user.</div>";
			} else {
				if ($userObj->update($editUserId, $editName, $editEmail, $editPassword)) {
					$message = "<div style='color:green;'>User edited successfully</div>";
				} else {
					$message = "<div style='color:red;'>Error happened during editing</div>";
				}
			}
		}
	}

	// --- 3. DELETE USER LOGIC ---
	else if (isset($_POST['delete-user-id'])) {
		$deleteUserId = $_POST['delete-user-id'];
		if($userObj->delete($deleteUserId)) {
			$message = "<div style='color:green;'>User deleted successfully</div>";
		} else {
			$message = "<div style='color:red;'>Failed to delete user</div>";
		}
	}

	// --- 4. TOGGLE ACTIVATION STATUS ---
	else if (isset($_POST['toggle-user-id'])) {
		$toggleUserId = $_POST['toggle-user-id'];
		$currentStatus = $_POST['current-status'];
		if($userObj->toggleActivation($toggleUserId, $currentStatus)) {
			$message = "<div style='color:green;'>User activation status updated.</div>";
		}
	}
}

$users = $userObj->getAll() ?: [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>User Management</title>
	<link rel="stylesheet" href="./styles/styles.css">
</head>
<body>
<nav>
	<a href="admin_dashboard.php">Back to Dashboard</a>
	<a href="logout.php">Logout</a>
</nav>

<h2>Manage Users</h2>

<div style="font-weight: bold; margin-bottom: 20px;">
	<?= $message ?>
</div>

<form action="" method="POST">
	<fieldset>
		<legend>Create New User</legend>
		<label for="name">Name :</label>
		<input type="text" name="name" placeholder="Ex: Ayham Zaid">

		<label for="email">Email :</label>
		<input type="email" name="email" placeholder="Ex: yourEmail@gmail.com">

		<label for="password">Password :</label>
		<input type="password" name="password" placeholder="***********">

		<button type="submit" name="btn_create">Create User</button>
	</fieldset>
</form>

<br><br>

<form action="" method="POST">
	<fieldset>
		<legend>Edit Form (Fill this out, then click 'Edit' in the table below)</legend>
		<label for="edit-name">Name :</label>
		<input type="text" name="edit-name" placeholder="New Name">

		<label for="edit-email">Email :</label>
		<input type="email" name="edit-email" placeholder="New Email">

		<label for="edit-password">Password :</label>
		<input type="password" name="edit-password" placeholder="New Password">
	</fieldset>

	<table border="1" style="margin-top: 20px; width: 100%; text-align: left;" cellpadding="10">
		<thead>
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>Email</th>
				<th>Status</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($users as $user): ?>
				<tr>
					<td><?= htmlspecialchars($user["id"]) ?></td>
					<td><?= htmlspecialchars($user["name"]) ?></td>
					<td><?= htmlspecialchars($user["email"]) ?></td>
					<td><?= $user["is_active"] ? 'Active' : 'Deactivated' ?></td>
					<td>
						<!-- Edit Button (submits the edit fields above with this user's ID) -->
						<button name="edit-user-id" value="<?= $user["id"]?>" type="submit">Edit</button>

						<!-- Delete Button -->
						<button name="delete-user-id" value="<?= $user["id"]?>" type="submit" formnovalidate>Delete</button>

						<!-- Activation Toggle Button -->
						<input type="hidden" name="current-status" value="<?= $user["is_active"] ?>">
						<button name="toggle-user-id" value="<?= $user["id"]?>" type="submit" formnovalidate>
							<?= $user["is_active"] ? 'Deactivate' : 'Activate' ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</form>

</body>
</html>
