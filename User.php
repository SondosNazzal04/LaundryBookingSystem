<?php

class User {
	private $pdo;
	private $name;
	private $email;
	private $hashed_password;

	public function __construct($db_connection, $name, $email, $password) {
		$this->pdo = $db_connection;
		$this->name = $name;
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
			throw new InvalidArgumentException("Invalid Email Format");
		$this->email = $email;
		$this->hashed_password = password_hash($password, PASSWORD_DEFAULT);
	}

	// public function get_name() {
	// 	return $this->name;
	// }

	// public function get_email() {
	// 	return $this->email;
	// }

	// public function get_hashed_password() {
	// 	return $this->hashed_password;
	// }

	public function register() {
		$sql = 'INSERT INTO users(name, email, password) VALUES(:name, :email, :password)';
		$stmt = $this->pdo->prepare($sql);
		try {
			return $stmt->execute([
				'name' => $this->name,
				'email' => $this->email,
				'password' => $this->hashed_password
			]);
		} catch(PDOException $e) {
			die("MySQL Error: " . $e->getMessage());
		}
	}
}

?>
