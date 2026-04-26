<?php
class Inventory {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function setupTable() {
        $sql = "CREATE TABLE IF NOT EXISTS bahan_mentah (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(100),
            stok DECIMAL(10,2),
            satuan VARCHAR(20)
        )";
        return $this->db->query($sql);
    }

    public function addMaterial($nama, $stok, $satuan) {
        $nama = $this->db->escape($nama);
        $satuan = $this->db->escape($satuan);
        $sql = "INSERT INTO bahan_mentah (nama, stok, satuan) VALUES ('$nama', '$stok', '$satuan') 
                ON DUPLICATE KEY UPDATE stok = stok + $stok";
        return $this->db->query($sql);
    }

    public function updateMaterial($id, $nama, $stok, $satuan) {
        $nama = $this->db->escape($nama);
        $satuan = $this->db->escape($satuan);
        $sql = "UPDATE bahan_mentah SET nama='$nama', stok='$stok', satuan='$satuan' WHERE id=$id";
        return $this->db->query($sql);
    }

    public function deleteMaterial($id) {
        $sql = "DELETE FROM bahan_mentah WHERE id=$id";
        return $this->db->query($sql);
    }

    public function getAllMaterials() {
        $query = $this->db->query("SELECT * FROM bahan_mentah");
        $materials = [];
        while ($row = $this->db->fetch_assoc($query)) {
            $materials[$row['id']] = $row;
        }
        return $materials;
    }

    public function getMaterialById($id) {
        $query = $this->db->query("SELECT * FROM bahan_mentah WHERE id=$id");
        return $this->db->fetch_assoc($query);
    }

    public function getTotalMaterials() {
        $query = $this->db->query("SELECT COUNT(*) as total FROM bahan_mentah");
        $result = $this->db->fetch_assoc($query);
        return $result['total'];
    }

    public function reduceMaterialStock($id, $quantity) {
        $sql = "UPDATE bahan_mentah SET stok = stok - $quantity WHERE id = $id";
        return $this->db->query($sql);
    }
}
?>
