<?php
// db/DB.php

class DB {
    private $host = 'localhost';
    private $dbname = 'trading_pos'; 
    private $username = 'root';
    private $password = '';
    private $pdo;

    public function connect() {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO("mysql:host={$this->host};dbname={$this->dbname};charset=utf8", $this->username, $this->password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }
}
?>
