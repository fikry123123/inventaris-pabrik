<?php
class Production {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function setupTables() {
        $sql1 = "CREATE TABLE IF NOT EXISTS produksi_wip (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama_produk VARCHAR(100),
            qty INT,
            tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $sql2 = "CREATE TABLE IF NOT EXISTS barang_keluar (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama_barang VARCHAR(100),
            qty INT,
            tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql1);
        return $this->db->query($sql2);
    }

    public function startProduction($id_produk, $qty_produksi, $recipe, $inventory) {
        $id_produk = (int)$id_produk;
        $qty_produksi = (int)$qty_produksi;
        $recipes = $recipe->getRecipesByProduct($id_produk);
        
        // Kurangi stok bahan baku
        foreach ($recipes as $r) {
            $total_potong = $r['qty_butuh'] * $qty_produksi;
            $inventory->reduceMaterialStock($r['id_bahan'], $total_potong);
        }

        // Tambah ke WIP (Dengan pengecekan mencegah Null)
        $query_p = $this->db->query("SELECT nama_produk FROM master_produk WHERE id=$id_produk");
        $data_p = $this->db->fetch_assoc($query_p);
        
        if ($data_p) {
            $nama_p = $this->db->escape($data_p['nama_produk']);
            $sql = "INSERT INTO produksi_wip (nama_produk, qty) VALUES ('$nama_p', $qty_produksi)";
            return $this->db->query($sql);
        }
        return false;
    }

    public function completeProduction($id_wip) {
        $id_wip = (int)$id_wip;
        $wip_data = $this->db->query("SELECT * FROM produksi_wip WHERE id = $id_wip");
        $data = $this->db->fetch_assoc($wip_data);
        
        // CEK APAKAH DATA DITEMUKAN (Mencegah error jika ter-submit 2 kali)
        if ($data) {
            $nama_barang = $this->db->escape($data['nama_produk']);
            $qty = (int)$data['qty'];
            
            $sql1 = "INSERT INTO barang_keluar (nama_barang, qty) VALUES ('$nama_barang', $qty)";
            $sql2 = "DELETE FROM produksi_wip WHERE id = $id_wip";
            
            $this->db->query($sql1);
            return $this->db->query($sql2);
        }
        
        // Kembalikan false jika data WIP sudah tidak ada/terhapus
        return false;
    }

    public function getWIPList() {
        $query = $this->db->query("SELECT * FROM produksi_wip");
        return $this->db->fetch_all($query);
    }

    public function getTotalWIP() {
        $query = $this->db->query("SELECT SUM(qty) as total FROM produksi_wip");
        $result = $this->db->fetch_assoc($query);
        return $result['total'] ?? 0;
    }

    public function getOutboundList() {
        $query = $this->db->query("SELECT * FROM barang_keluar ORDER BY tanggal DESC");
        return $this->db->fetch_all($query);
    }
}
?>