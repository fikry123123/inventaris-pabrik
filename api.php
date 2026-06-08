<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");

require_once 'Database.php';
require_once 'User.php';
require_once 'Inventory.php';
require_once 'Recipe.php';
require_once 'Production.php';

$db = new Database();
$user = new User($db);
$inventory = new Inventory($db);
$recipe = new Recipe($db);
$production = new Production($db);

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"));

switch ($action) {
    // --- AUTH & DASHBOARD ---
    case 'login':
        $u = $db->escape($data->username ?? '');
        $p = $data->password ?? '';
        $q = $db->query("SELECT * FROM users WHERE username = '$u'");
        $row = $db->fetch_assoc($q);
        if ($row && password_verify($p, $row['password'])) {
            echo json_encode(['status' => 'success', 'user' => ['id' => $row['id'], 'username' => $row['username'], 'role' => $row['role']]]);
        } else { echo json_encode(['status' => 'error', 'message' => 'Login Gagal']); }
        break;
    case 'get_dashboard':
        $products = $recipe->getAllProducts();
        $proyeksi = [];
        foreach($products as $p) {
            $proyeksi[] = ['nama_produk' => $p['nama_produk'], 'kapasitas' => $recipe->getProductCapacity($p['id'])];
        }
        echo json_encode(['total_materials' => (int)$inventory->getTotalMaterials(), 'total_wip' => (int)$production->getTotalWIP(), 'proyeksi' => $proyeksi]);
        break;

    // --- USERS (Admin Only) ---
    case 'get_users':
        echo json_encode(array_values($user->getAllUsers()));
        break;
    case 'add_user':
        echo json_encode(['status' => $user->addUser($data->username, $data->password, $data->role) ? 'success' : 'error']);
        break;
    case 'edit_user':
        echo json_encode(['status' => $user->editUser($data->id, $data->username, $data->password ?? '', $data->role) ? 'success' : 'error']);
        break;
    case 'delete_user':
        echo json_encode(['status' => $user->deleteUser($data->id) ? 'success' : 'error']);
        break;

    // --- INVENTORY ---
    case 'get_inventory':
        echo json_encode(array_values($inventory->getAllMaterials()));
        break;
    case 'add_material':
        echo json_encode(['status' => $inventory->addMaterial($data->nama, $data->stok, $data->satuan) ? 'success' : 'error']);
        break;
    case 'edit_material':
        echo json_encode(['status' => $inventory->updateMaterial($data->id, $data->nama, $data->stok, $data->satuan) ? 'success' : 'error']);
        break;
    case 'delete_material':
        echo json_encode(['status' => $inventory->deleteMaterial($data->id) ? 'success' : 'error']);
        break;

    // --- RECIPES ---
    case 'get_recipes':
        $products = $recipe->getAllProducts();
        $details = $recipe->getAllRecipesGrouped();
        $result = [];
        foreach($products as $p) {
            $p['bom'] = $details[$p['id']] ?? [];
            $result[] = $p;
        }
        echo json_encode($result);
        break;
    case 'add_recipe':
        echo json_encode(['status' => $recipe->createRecipe($data->nama_produk, $data->bahan_list, $data->qty_list) ? 'success' : 'error']);
        break;
    case 'edit_recipe':
        echo json_encode(['status' => $recipe->updateRecipe($data->id_produk, $data->nama_produk, $data->bahan_list, $data->qty_list) ? 'success' : 'error']);
        break;
    case 'delete_recipe':
        echo json_encode(['status' => $recipe->deleteRecipe($data->id_produk) ? 'success' : 'error']);
        break;

    // --- PRODUCTION & OUTBOUND ---
    case 'get_products':
        echo json_encode(array_values($recipe->getAllProducts()));
        break;
    case 'get_wip':
        echo json_encode(array_values($production->getWIPList()));
        break;
    case 'process_production':
        echo json_encode(['status' => $production->startProduction($data->id_produk, $data->qty, $recipe, $inventory) ? 'success' : 'error']);
        break;
    case 'complete_wip':
        echo json_encode(['status' => $production->completeProduction($data->id_wip) ? 'success' : 'error']);
        break;
    case 'get_outbound':
        echo json_encode(array_values($production->getOutboundList()));
        break;

    default: echo json_encode(['message' => 'Invalid Action']); break;
}

// Mencari kapasitas produksi maksimal berdasarkan stok saat ini
function getMaxCapacity($id_produk, $db, $recipe) {
    $bom = $recipe->getRecipeDetails($id_produk);
    $max_possible = null;

    foreach ($bom as $b) {
        $id_bahan = $b['id_bahan'];
        $butuh_per_unit = $b['qty_butuh'];

        // Ambil stok bahan saat ini
        $q = $db->query("SELECT stok FROM materials WHERE id = $id_bahan");
        $res = $db->fetch_assoc($q);
        $stok_sekarang = $res['stok'];

        // Hitung berapa unit yang bisa dibuat dengan bahan ini
        $kapasitas_bahan_ini = floor($stok_sekarang / $butuh_per_unit);

        // Cari angka terkecil (Bottle Neck)
        if ($max_possible === null || $kapasitas_bahan_ini < $max_possible) {
            $max_possible = $kapasitas_bahan_ini;
        }
    }
    return ($max_possible < 0) ? 0 : $max_possible;
}
?>