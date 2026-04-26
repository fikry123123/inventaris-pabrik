<?php
class Recipe {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function setupTables() {
        $sql1 = "CREATE TABLE IF NOT EXISTS master_produk (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama_produk VARCHAR(100)
        )";
        
        $sql2 = "CREATE TABLE IF NOT EXISTS resep (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_produk INT,
            id_bahan INT,
            qty_butuh DECIMAL(10,2)
        )";
        
        $this->db->query($sql1);
        return $this->db->query($sql2);
    }

    public function createRecipe($nama_produk, $bahan_list, $qty_list) {
        $nama_produk = $this->db->escape($nama_produk);
        $sql = "INSERT INTO master_produk (nama_produk) VALUES ('$nama_produk')";
        
        if ($this->db->query($sql)) {
            $id_produk = $this->db->insert_id();
            
            for ($i = 0; $i < count($bahan_list); $i++) {
                if (!empty($bahan_list[$i]) && !empty($qty_list[$i])) {
                    $sql_resep = "INSERT INTO resep (id_produk, id_bahan, qty_butuh) 
                                  VALUES ($id_produk, {$bahan_list[$i]}, {$qty_list[$i]})";
                    $this->db->query($sql_resep);
                }
            }
            return $id_produk;
        }
        return false;
    }

    public function getAllProducts() {
        $query = $this->db->query("SELECT * FROM master_produk");
        return $this->db->fetch_all($query);
    }

    public function getRecipesByProduct($id_produk) {
        $sql = "SELECT r.*, b.nama as nama_bahan, b.stok, b.satuan 
                FROM resep r 
                JOIN bahan_mentah b ON r.id_bahan = b.id 
                WHERE r.id_produk = $id_produk";
        $query = $this->db->query($sql);
        return $this->db->fetch_all($query);
    }

    public function getAllRecipesGrouped() {
        $sql = "SELECT r.*, b.nama as nama_bahan, b.stok, b.satuan 
                FROM resep r 
                JOIN bahan_mentah b ON r.id_bahan = b.id";
        $query = $this->db->query($sql);
        
        $recipes = [];
        while ($row = $this->db->fetch_assoc($query)) {
            $recipes[$row['id_produk']][] = $row;
        }
        return $recipes;
    }

    public function getProductCapacity($id_produk) {
        $recipes = $this->getRecipesByProduct($id_produk);
        $max_capacity = 999999;

        foreach ($recipes as $recipe) {
            $potensi = floor($recipe['stok'] / $recipe['qty_butuh']);
            if ($potensi < $max_capacity) {
                $max_capacity = $potensi;
            }
        }

        return $max_capacity > 999999 ? 0 : $max_capacity;
    }
    public function deleteRecipe($id_produk) {
        $id_produk = (int)$id_produk;
        // Hapus detail resep dulu
        $this->db->query("DELETE FROM resep WHERE id_produk = $id_produk");
        // Hapus master produknya
        return $this->db->query("DELETE FROM master_produk WHERE id = $id_produk");
    }
}
?>
