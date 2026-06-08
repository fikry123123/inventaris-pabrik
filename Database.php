<?php
class Database {
    private $conn;
    private $host = "acela.proxy.rlwy.net";
    private $user = "root";
    private $password = "EXdeVuaCoVntmAstxBrjJdtKNPPNhTKp";
    private $database = "railway";

    public function __construct() {
        $this->connect();
    }

    public function connect() {
        $this->conn = mysqli_connect($this->host, $this->user, $this->password, $this->database);
        
        if (!$this->conn) {
            die("Connection Failed: " . mysqli_connect_error());
        }
        
        return $this->conn;
    }

    public function query($sql) {
        return mysqli_query($this->conn, $sql);
    }

    public function escape($data) {
        return mysqli_real_escape_string($this->conn, $data);
    }

    public function fetch_assoc($result) {
        return mysqli_fetch_assoc($result);
    }

    public function fetch_all($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    public function num_rows($result) {
        return mysqli_num_rows($result);
    }

    public function insert_id() {
        return mysqli_insert_id($this->conn);
    }

    public function close() {
        mysqli_close($this->conn);
    }
}
?>
