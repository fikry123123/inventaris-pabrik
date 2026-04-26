<?php
class User {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function setupTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE,
            password VARCHAR(255),
            role ENUM('admin', 'editor', 'reviewer') NOT NULL
        )";
        return $this->db->query($sql);
    }

    public function createDefaultAdmin() {
        $check = $this->db->query("SELECT * FROM users WHERE role='admin'");
        
        if ($this->db->num_rows($check) == 0) {
            $default_pass = password_hash('admin123', PASSWORD_DEFAULT);
            $username = $this->db->escape('admin');
            $sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$default_pass', 'admin')";
            return $this->db->query($sql);
        }
        return false;
    }

    public function login($username, $password) {
        $username = $this->db->escape($username);
        $query = $this->db->query("SELECT * FROM users WHERE username = '$username'");
        
        if ($user = $this->db->fetch_assoc($query)) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
        }
        return false;
    }

    public function addUser($username, $password, $role) {
        $username = $this->db->escape($username);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$password_hash', '$role')";
        return $this->db->query($sql);
    }
    public function editUser($id, $username, $password, $role) {
        $id = (int)$id;
        $username = $this->db->escape($username);
        $role = $this->db->escape($role);

        // Jika password diisi, update passwordnya juga. Jika kosong, biarkan yang lama.
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username='$username', password='$password_hash', role='$role' WHERE id=$id";
        } else {
            $sql = "UPDATE users SET username='$username', role='$role' WHERE id=$id";
        }
        
        return $this->db->query($sql);
    }

    public function deleteUser($id) {
        if ($id != $_SESSION['user_id']) {
            $sql = "DELETE FROM users WHERE id=$id";
            return $this->db->query($sql);
        }
        return false;
    }

    public function getAllUsers() {
        $query = $this->db->query("SELECT * FROM users");
        return $this->db->fetch_all($query);
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getRole() {
        return $_SESSION['role'] ?? null;
    }

    public function getUsername() {
        return $_SESSION['username'] ?? null;
    }

    public function logout() {
        session_destroy();
    }
}
?>
