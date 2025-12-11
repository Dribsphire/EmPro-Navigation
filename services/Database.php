<?php
class Database {

    private $host = "localhost";
    private $db_name = "empro_navigation";
    private $username = "root";
    private $password = "";


    //private $host = "localhost";
    //private $db_name = "u719275046_empro_nav";
    //private $username = "u719275046_empro_nav";
    //private $password = "F8m=;lVdxlbd";

    
    public $conn = null;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
