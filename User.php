<?php

class User {
	private $pdo;
	private $name;
	private $email;
	private $hashed_password;
	private $role; // New property

	// Added $role to the constructor with a default value of 'user'
	public function __construct($db_connection, $name = '', $email = '', $password = '', $role = 'user') {
		$this->pdo = $db_connection;

		if ($name && $email && $password) {
			$this->name = $name;
			if(!filter_var($email, FILTER_VALIDATE_EMAIL))
				throw new InvalidArgumentException("Invalid Email Format");
			$this->email = $email;
			$this->hashed_password = password_hash($password, PASSWORD_DEFAULT);
			$this->role = $role;
		}
	}

	public function register() {
		// Updated SQL to insert the dynamic role
		$sql = 'INSERT INTO users(name, email, password, role, is_active) VALUES(:name, :email, :password, :role, 1)';
		$stmt = $this->pdo->prepare($sql);
		try {
			return $stmt->execute([
				'name' => $this->name,
				'email' => $this->email,
				'password' => $this->hashed_password,
				'role' => $this->role
			]);
		} catch(PDOException $e) {
			die("MySQL Error: " . $e->getMessage());
		}
	}

	public function login($email, $password) {
		$sql = 'SELECT * FROM users WHERE email = :email LIMIT 1';
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute(['email' => $email]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($user && password_verify($password, $user['password'])) {
			// Business Rule: Deactivated users must not be able to log in
			if ($user['is_active'] == 0) {
				throw new Exception("Your account has been deactivated by an admin.");
			}

			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}
			$_SESSION['user_id'] = $user['id'];
			$_SESSION['name'] = $user['name'];
			$_SESSION['role'] = $user['role'];

			return $user['role'];
		}
		throw new Exception("Invalid email or password.");
	}

	public function emailExists($email, $exclude_id = null) {$sql = 'SELECT id FROM users WHERE email = :email';
		$params = ['email' =>$email];

		if ($exclude_id) {$sql .= ' AND id != :id';
			$params['id'] =$exclude_id;
		}

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);
		return $stmt->rowCount() > 0;
	}

	public function getAll() {
		// Fetch users, but exclude admins so you don't accidentally delete yourself
		$stmt =$this->pdo->query("SELECT * FROM users WHERE role = 'user'");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function update($id,$name, $email,$password) {
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);
		$sql = 'UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id';$stmt = $this->pdo->prepare($sql);
		return $stmt->execute([
			'name' => $name,
			'email' => $email,
			'password' => $hashed_password,
			'id' => $id
		]);
	}

	public function delete($id) {
		$sql = 'DELETE FROM users WHERE id = :id';$stmt = $this->pdo->prepare($sql);
		return $stmt->execute(['id' =>$id]);
	}

	public function toggleActivation($id,$current_status) {
		$new_status =$current_status == 1 ? 0 : 1;
		$sql = 'UPDATE users SET is_active = :status WHERE id = :id';$stmt = $this->pdo->prepare($sql);
		return $stmt->execute(['status' => $new_status, 'id' =>$id]);
	}
}
?>
