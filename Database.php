<?php
class Database {
	private $host = "localhost";
	private $username = "root";
	private $password = "";
	private $dbname = "laundry_booking";
	public $pdo;

	public function getConnection() {
		$this->pdo = null;
		try {
			$this->pdo = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbname, $this->username, $this->password);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			die("Connection failed: " . $e->getMessage());
		}
		return $this->pdo;
	}
}
