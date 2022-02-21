<?php
class Database extends PDO {
    private $DB_HOST = 'localhost';
    private $DB_USER = 'root';
    private $DB_PASS = 'root';
    private $DB_NAME = 'onlinechess';

    public function __construct() {
        try {
            parent::__construct("mysql:host=".$this->DB_HOST.";dbname=".$this->DB_NAME, $this->DB_USER, $this->DB_PASS);
        } catch(PDOException $exception) {
            echo "Erreur de connexion : ". $exception->getMessage();
        }
    }
}