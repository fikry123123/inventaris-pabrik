<?php
session_start();

// Load Classes dari folder yang sama
require_once 'Database.php';
require_once 'User.php';
require_once 'Inventory.php';
require_once 'Recipe.php';
require_once 'Production.php';

// Initialize Database
$db = new Database();

// Initialize Classes
$user = new User($db);
$inventory = new Inventory($db);
$recipe = new Recipe($db);
$production = new Production($db);

// Setup Tables
$user->setupTable();
$user->createDefaultAdmin();
$inventory->setupTable();
$recipe->setupTables();
$production->setupTables();

// Handle Logout
if (isset($_GET['logout'])) {
    $user->logout();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Login
$login_error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_action'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($user->login($username, $password)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Username atau Password salah!";
    }
}

// Check Login
if (!$user->isLoggedIn()) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Pabrik Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-10 rounded-3xl shadow-xl w-96 border border-slate-200">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4"><i data-lucide="factory" size="32"></i></div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tighter">PABRIK<span class="text-blue-500">PRO</span></h1>
            <p class="text-sm text-slate-500 font-medium mt-1">Silakan login untuk melanjutkan</p>
        </div>
        
        <?php if($login_error): ?>
            <div class="bg-rose-100 text-rose-600 p-3 rounded-xl mb-4 text-sm font-bold text-center"><?php echo $login_error; ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="login_action" value="1">
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase">Username</label>
                <input type="text" name="username" class="w-full p-4 border bg-slate-50 rounded-xl mt-1 font-bold focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all" required>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase">Password</label>
                <input type="password" name="password" class="w-full p-4 border bg-slate-50 rounded-xl mt-1 font-bold focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white font-black py-4 rounded-xl shadow-lg hover:bg-blue-700 transition-all">LOGIN</button>
        </form>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
<?php 
    exit; 
}

// Get User Info
$user_role = $user->getRole();
$user_name = $user->getUsername();

// Handle Form
$notif = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($user_role == 'admin') {
        if ($action == 'tambah_user') {
            if ($user->addUser($_POST['username'], $_POST['password'], $_POST['role'])) {
                $notif = "User baru berhasil ditambahkan!";
            }
        }
        if ($action == 'hapus_user') {
            if ($user->deleteUser($_POST['id_user'])) {
                $notif = "User berhasil dihapus!";
            }
        }
        
    }

    if ($user_role == 'admin' || $user_role == 'editor') {
        if ($action == 'tambah_bahan') {
            $inventory->addMaterial($_POST['nama'], $_POST['stok'], $_POST['satuan']);
            $notif = "Bahan baku berhasil ditambahkan!";
        }
        if ($action == 'edit_bahan') {
            $inventory->updateMaterial($_POST['id'], $_POST['nama'], $_POST['stok'], $_POST['satuan']);
            $notif = "Data bahan baku diperbarui!";
        }
        if ($action == 'hapus_bahan') {
            $inventory->deleteMaterial($_POST['id']);
            $notif = "Bahan baku dihapus!";
        }

        if ($action == 'buat_resep_baru') {
            $recipe->createRecipe($_POST['nama_produk'], $_POST['id_bahan'], $_POST['qty_butuh']);
            $notif = "Produk dan Resep baru berhasil disimpan!";
        }
        if ($action == 'proses_produksi') {
            $production->startProduction($_POST['id_produk'], $_POST['qty_produksi'], $recipe, $inventory);
            $notif = "Berhasil memproses " . $_POST['qty_produksi'] . " unit!";
        }
        if ($action == 'barang_keluar') {
            $production->completeProduction($_POST['id_wip']);
            $notif = "Barang berhasil dikeluarkan!";
        }
    } else {
        if ($action != '') {
            $notif = "Akses Ditolak! Reviewer tidak dapat mengubah data.";
        }
    }
}

// Prepare Data
$db_bahan = $inventory->getAllMaterials();
$db_resep = $recipe->getAllRecipesGrouped();
$products = $recipe->getAllProducts();
$wip_list = $production->getWIPList();
$total_wip = $production->getTotalWIP();
$total_materials = $inventory->getTotalMaterials();
$all_users = $user->getAllUsers();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Pabrik Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .sidebar-item.active { background: #1e293b; color: #38bdf8; border-right: 4px solid #38bdf8; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden font-sans">

    <aside class="w-72 bg-[#0f172a] text-slate-400 flex flex-col shadow-2xl z-20">
        <div class="p-8 border-b border-slate-800">
            <h1 class="text-2xl font-black text-white tracking-tighter flex items-center gap-2">
                <i data-lucide="factory" class="text-blue-500"></i> PABRIK<span class="text-blue-500">PRO</span>
            </h1>
        </div>
        
        <div class="p-4 bg-slate-800/50 mx-4 mt-4 rounded-xl flex items-center gap-3 border border-slate-700/50">
            <div class="w-10 h-10 bg-slate-700 rounded-full flex items-center justify-center text-white font-bold"><i data-lucide="user"></i></div>
            <div class="flex-1 overflow-hidden">
                <p class="text-sm font-bold text-white truncate"><?php echo strtoupper($user_name); ?></p>
                <p class="text-[10px] font-black tracking-widest uppercase <?php echo $user_role == 'admin' ? 'text-emerald-400' : ($user_role == 'editor' ? 'text-blue-400' : 'text-amber-400'); ?>"><?php echo $user_role; ?></p>
            </div>
            <a href="?logout=true" class="text-slate-400 hover:text-rose-400 transition-colors p-2" title="Logout"><i data-lucide="log-out" size="18"></i></a>
        </div>

        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <button onclick="showTab('dash', this)" class="sidebar-item active w-full flex items-center gap-4 p-4 rounded-xl font-bold transition-all"><i data-lucide="layout-dashboard"></i> Dashboard</button>
            <div class="pt-4 pb-2 text-[10px] font-black uppercase tracking-widest px-4">Gudang & Produksi</div>
            <button onclick="showTab('inventory', this)" class="sidebar-item w-full flex items-center gap-4 p-4 rounded-xl font-bold transition-all"><i data-lucide="package"></i> Data Bahan Baku</button>
            <button onclick="showTab('resep', this)" class="sidebar-item w-full flex items-center gap-4 p-4 rounded-xl font-bold transition-all"><i data-lucide="file-plus"></i> Resep Produk</button>
            <button onclick="showTab('proses', this)" class="sidebar-item w-full flex items-center gap-4 p-4 rounded-xl font-bold transition-all"><i data-lucide="anvil"></i> Proses & WIP</button>
            
            <?php if($user_role == 'admin'): ?>
            <div class="pt-4 pb-2 text-[10px] font-black uppercase tracking-widest px-4">Sistem</div>
            <button onclick="showTab('users', this)" class="sidebar-item w-full flex items-center gap-4 p-4 rounded-xl font-bold transition-all"><i data-lucide="users"></i> Manajemen User</button>
            <?php endif; ?>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center px-8 justify-between shadow-sm">
            <h2 id="tab-title" class="text-xl font-black text-slate-800 uppercase tracking-tight">Dashboard</h2>
            <div class="text-sm font-bold text-slate-400"><?php echo date('d M Y'); ?></div>
        </header>

        <div class="flex-1 p-8 overflow-y-auto">
            <?php if($notif != ""): ?>
                <div class="bg-blue-600 text-white p-4 rounded-xl mb-6 font-bold shadow-lg flex items-center gap-3">
                    <i data-lucide="info"></i> <?php echo $notif; ?>
                </div>
            <?php endif; ?>

            <div id="tab-dash" class="tab-content active space-y-8">
                <div class="grid grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Bahan Baku</p>
                            <h3 class="text-3xl font-black mt-1"><?php echo $total_materials; ?> Jenis</h3>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center"><i data-lucide="box"></i></div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Sedang Diproses (WIP)</p>
                            <h3 class="text-3xl font-black mt-1 text-amber-500"><?php echo $total_wip ?: 0; ?> Unit</h3>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center"><i data-lucide="settings"></i></div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
                    <h3 class="font-black text-slate-800 mb-6 flex items-center gap-2"><i data-lucide="bar-chart-2" class="text-blue-500"></i> Proyeksi Kapasitas Produksi</h3>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b">
                            <tr><th class="p-4">Nama Produk</th><th class="p-4">Kebutuhan Resep</th><th class="p-4">Bisa Diproduksi (Max)</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $p): 
                                $id_p = $p['id'];
                                $max_bisa = 999999;
                                $resep_str = [];
                                if(isset($db_resep[$id_p])) {
                                    foreach($db_resep[$id_p] as $r) {
                                        $resep_str[] = "{$r['qty_butuh']} {$r['satuan']} {$r['nama_bahan']}";
                                        $potensi = floor($r['stok'] / $r['qty_butuh']);
                                        if($potensi < $max_bisa) $max_bisa = $potensi;
                                    }
                                } else { $max_bisa = 0; }
                            ?>
                            <tr class="border-b">
                                <td class="p-4 font-bold"><?php echo $p['nama_produk']; ?></td>
                                <td class="p-4 text-xs text-slate-500"><?php echo implode(" + ", $resep_str); ?></td>
                                <td class="p-4 font-black <?php echo $max_bisa > 0 ? 'text-emerald-500' : 'text-rose-500'; ?>"><?php echo $max_bisa; ?> Unit</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-inventory" class="tab-content space-y-6">
                <?php if($user_role != 'reviewer'): ?>
                <div class="bg-white p-6 rounded-2xl border shadow-sm">
                    <h3 id="form-bahan-title" class="font-bold mb-4">Tambah Bahan Baku Baru</h3>
                    <form method="POST" id="form-bahan" class="flex gap-4">
                        <input type="hidden" name="action" id="bahan-action" value="tambah_bahan">
                        <input type="hidden" name="id" id="bahan-id" value="">
                        <input type="text" name="nama" id="bahan-nama" placeholder="Nama Bahan" class="flex-1 p-3 border rounded-xl bg-slate-50" required>
                        <input type="number" step="0.1" name="stok" id="bahan-stok" placeholder="Stok" class="w-32 p-3 border rounded-xl bg-slate-50" required>
                        <input type="text" name="satuan" id="bahan-satuan" placeholder="Satuan (Pcs/Kg)" class="w-32 p-3 border rounded-xl bg-slate-50" required>
                        <button type="submit" id="bahan-btn" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-bold">Simpan</button>
                        <button type="button" onclick="resetBahanForm()" class="bg-slate-200 px-4 rounded-xl"><i data-lucide="x"></i></button>
                    </form>
                </div>
                <?php else: ?>
                <div class="bg-amber-50 text-amber-600 p-4 rounded-xl font-bold border border-amber-200 flex items-center gap-2"><i data-lucide="eye"></i> Mode Reviewer: Anda hanya dapat melihat data.</div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b">
                            <tr>
                                <th class="p-4">Nama</th><th class="p-4">Stok</th>
                                <?php if($user_role != 'reviewer'): ?><th class="p-4 text-right">Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($db_bahan as $b): ?>
                            <tr class="border-b">
                                <td class="p-4 font-bold text-slate-700"><?php echo $b['nama']; ?></td>
                                <td class="p-4"><?php echo $b['stok']; ?> <span class="text-xs text-slate-400"><?php echo $b['satuan']; ?></span></td>
                                <?php if($user_role != 'reviewer'): ?>
                                <td class="p-4 text-right flex justify-end gap-2">
                                    <button onclick="editBahan(<?php echo $b['id']; ?>, '<?php echo $b['nama']; ?>', <?php echo $b['stok']; ?>, '<?php echo $b['satuan']; ?>')" class="text-blue-500 bg-blue-50 p-2 rounded-lg"><i data-lucide="edit" size="16"></i></button>
                                    <form method="POST" onsubmit="return confirm('Hapus? Resep yang pakai bahan ini akan ikut terhapus!');">
                                        <input type="hidden" name="action" value="hapus_bahan">
                                        <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" class="text-rose-500 bg-rose-50 p-2 rounded-lg"><i data-lucide="trash-2" size="16"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-resep" class="tab-content">
                <?php if($user_role != 'reviewer'): ?>
                <div class="bg-white p-8 rounded-3xl shadow-sm border max-w-2xl">
                    <h3 class="text-lg font-black mb-6">Buat Master Barang & Resep Baru</h3>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="buat_resep_baru">
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase">Nama Barang Baru</label>
                            <input type="text" name="nama_produk" class="w-full p-4 border bg-slate-50 rounded-xl mt-1 font-bold" placeholder="Misal: Lemari Kaca" required>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase flex justify-between items-end mb-2">
                                <span>Komponen Bahan Baku (BOM)</span>
                                <button type="button" onclick="tambahBarisResep()" class="text-blue-600 bg-blue-50 px-3 py-1 rounded-lg flex items-center gap-1">+ Tambah Bahan</button>
                            </label>
                            <div id="resep-container" class="space-y-3">
                                <div class="flex gap-3">
                                    <select name="id_bahan[]" class="flex-1 p-3 border bg-slate-50 rounded-xl" required>
                                        <option value="">-- Pilih Bahan Baku --</option>
                                        <?php foreach($db_bahan as $b): ?><option value="<?php echo $b['id']; ?>"><?php echo $b['nama']; ?> (<?php echo $b['satuan']; ?>)</option><?php endforeach; ?>
                                    </select>
                                    <input type="number" step="0.1" name="qty_butuh[]" class="w-32 p-3 border bg-slate-50 rounded-xl" placeholder="Butuh Qty" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white font-black py-4 rounded-xl shadow-lg">SIMPAN BARANG BARU</button>
                    </form>
                </div>
                <?php else: ?>
                <div class="bg-amber-50 text-amber-600 p-4 rounded-xl font-bold border border-amber-200">Mode Reviewer: Anda tidak diizinkan menambahkan resep baru. Silakan lihat daftar resep di halaman Dashboard.</div>
                <?php endif; ?>
            </div>

            <div id="tab-proses" class="tab-content space-y-6">
                
                <?php if($user_role != 'reviewer'): ?>
                <div class="bg-white p-8 rounded-3xl shadow-sm border max-w-2xl">
                    <h3 class="text-lg font-black mb-6 text-amber-500 flex items-center gap-2"><i data-lucide="zap"></i> Eksekusi Produksi Baru</h3>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="proses_produksi">
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase">Pilih Barang</label>
                            <select name="id_produk" id="select-produk" onchange="kalkulasiKapasitas()" class="w-full p-4 border bg-slate-50 rounded-xl mt-1 font-bold" required>
                                <option value="">-- Pilih Barang yang Akan Dibuat --</option>
                                <?php foreach($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo $p['nama_produk']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="info-produksi" class="hidden p-4 rounded-xl border border-blue-100 bg-blue-50">
                            <p class="text-xs font-bold text-blue-600 uppercase mb-2">Status Ketersediaan Bahan</p>
                            <ul id="list-bahan-dibutuhkan" class="text-sm font-medium text-slate-700 space-y-1 mb-4"></ul>
                            <div class="flex items-center justify-between border-t border-blue-200 pt-3">
                                <span class="font-bold text-slate-700">Maksimal Bisa Dibuat:</span>
                                <span id="max-qty-label" class="text-xl font-black text-blue-700">0 Unit</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase">Jumlah Produksi (Qty)</label>
                            <input type="number" id="input-qty-produksi" name="qty_produksi" min="1" max="0" class="w-full p-4 border bg-slate-50 rounded-xl mt-1 font-black text-xl" placeholder="0" required disabled>
                        </div>
                        <button type="submit" id="btn-produksi" class="w-full bg-slate-900 text-white font-black py-4 rounded-xl shadow-lg opacity-50 cursor-not-allowed" disabled>MULAI PROSES</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                    <div class="p-4 bg-slate-50 border-b font-bold text-slate-700 flex justify-between items-center">
                        Daftar Barang Dalam Proses (WIP)
                        <?php if($user_role == 'reviewer'): ?><span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-md">View Only</span><?php endif; ?>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b">
                            <tr>
                                <th class="p-4">Barang di Proses</th><th class="p-4">Qty</th><th class="p-4">Tanggal Mulai</th>
                                <?php if($user_role != 'reviewer'): ?><th class="p-4 text-right">Aksi Outbound</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($wip_list as $w): ?>
                            <tr class="border-b">
                                <td class="p-4 font-bold text-slate-700"><?php echo $w['nama_produk']; ?></td>
                                <td class="p-4 font-black"><?php echo $w['qty']; ?></td>
                                <td class="p-4 text-xs text-slate-400"><?php echo $w['tanggal']; ?></td>
                                <?php if($user_role != 'reviewer'): ?>
                                <td class="p-4 text-right">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="barang_keluar">
                                        <input type="hidden" name="id_wip" value="<?php echo $w['id']; ?>">
                                        <button class="bg-emerald-500 text-white font-bold px-4 py-2 rounded-lg text-xs hover:bg-emerald-600">SELESAIKAN & KELUARKAN</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if($user_role == 'admin'): ?>
            <div id="tab-users" class="tab-content space-y-6">
                <div class="bg-white p-6 rounded-2xl border shadow-sm max-w-3xl">
                    <h3 class="font-bold mb-4">Tambah Akses User Baru</h3>
                    <form method="POST" class="flex gap-4 items-end">
                        <input type="hidden" name="action" value="tambah_user">
                        <div class="flex-1">
                            <label class="text-xs font-bold text-slate-400 uppercase">Username</label>
                            <input type="text" name="username" class="w-full p-3 border rounded-xl bg-slate-50 mt-1" required>
                        </div>
                        <div class="flex-1">
                            <label class="text-xs font-bold text-slate-400 uppercase">Password</label>
                            <input type="password" name="password" class="w-full p-3 border rounded-xl bg-slate-50 mt-1" required>
                        </div>
                        <div class="w-40">
                            <label class="text-xs font-bold text-slate-400 uppercase">Role</label>
                            <select name="role" class="w-full p-3 border rounded-xl bg-slate-50 mt-1" required>
                                <option value="editor">Editor</option>
                                <option value="reviewer">Reviewer</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold h-[50px]">Simpan</button>
                    </form>
                </div>

                <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b"><tr><th class="p-4">Username</th><th class="p-4">Role</th><th class="p-4 text-right">Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach($all_users as $u): ?>
                            <tr class="border-b">
                                <td class="p-4 font-bold text-slate-700"><?php echo $u['username']; ?></td>
                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider 
                                        <?php echo $u['role'] == 'admin' ? 'bg-emerald-100 text-emerald-700' : ($u['role'] == 'editor' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'); ?>">
                                        <?php echo $u['role']; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" onsubmit="return confirm('Hapus akses user ini?');">
                                        <input type="hidden" name="action" value="hapus_user">
                                        <input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="text-rose-500 bg-rose-50 p-2 rounded-lg"><i data-lucide="trash-2" size="16"></i></button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-xs text-slate-400 font-bold">Anda Sendiri</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <script>
        const dbResep = <?php echo json_encode($db_resep); ?>;
        
        function showTab(id, btn) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + id).classList.add('active');
            document.querySelectorAll('.sidebar-item').forEach(s => s.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-title').innerText = btn.innerText;
            lucide.createIcons();
        }

        // Script Form Bahan Baku (Khusus Editor/Admin)
        <?php if($user_role != 'reviewer'): ?>
        function editBahan(id, nama, stok, satuan) {
            document.getElementById('form-bahan-title').innerText = "Edit Bahan Baku";
            document.getElementById('bahan-action').value = "edit_bahan";
            document.getElementById('bahan-id').value = id;
            document.getElementById('bahan-nama').value = nama;
            document.getElementById('bahan-stok').value = stok;
            document.getElementById('bahan-satuan').value = satuan;
            document.getElementById('bahan-btn').innerText = "Update";
            document.getElementById('bahan-btn').classList.replace('bg-slate-900', 'bg-amber-500');
        }
        function resetBahanForm() {
            document.getElementById('form-bahan').reset();
            document.getElementById('form-bahan-title').innerText = "Tambah Bahan Baku Baru";
            document.getElementById('bahan-action').value = "tambah_bahan";
            document.getElementById('bahan-btn').innerText = "Simpan";
            document.getElementById('bahan-btn').classList.replace('bg-amber-500', 'bg-slate-900');
        }

        function tambahBarisResep() {
            const row = document.createElement('div');
            row.className = 'flex gap-3 mt-2';
            row.innerHTML = `
                <select name="id_bahan[]" class="flex-1 p-3 border bg-slate-50 rounded-xl" required>
                    <option value="">-- Pilih Bahan Baku --</option>
                    <?php foreach($db_bahan as $b): ?><option value="<?php echo $b['id']; ?>"><?php echo $b['nama']; ?> (<?php echo $b['satuan']; ?>)</option><?php endforeach; ?>
                </select>
                <input type="number" step="0.1" name="qty_butuh[]" class="w-32 p-3 border bg-slate-50 rounded-xl" placeholder="Butuh Qty" required>
            `;
            document.getElementById('resep-container').appendChild(row);
        }

        function kalkulasiKapasitas() {
            const idProduk = document.getElementById('select-produk').value;
            const infoDiv = document.getElementById('info-produksi');
            const listBahan = document.getElementById('list-bahan-dibutuhkan');
            const maxLabel = document.getElementById('max-qty-label');
            const inputQty = document.getElementById('input-qty-produksi');
            const btnProses = document.getElementById('btn-produksi');

            if (!idProduk || !dbResep[idProduk]) {
                infoDiv.classList.add('hidden');
                inputQty.disabled = true; inputQty.value = '';
                btnProses.disabled = true; btnProses.classList.add('opacity-50', 'cursor-not-allowed');
                return;
            }

            let maxProduksi = Infinity;
            listBahan.innerHTML = '';
            
            dbResep[idProduk].forEach(resep => {
                const potensi = Math.floor(resep.stok / resep.qty_butuh);
                if (potensi < maxProduksi) maxProduksi = potensi;
                
                const li = document.createElement('li');
                li.className = "flex justify-between";
                li.innerHTML = `<span><i data-lucide="check" class="inline w-3 h-3 text-emerald-500 mr-1"></i> ${resep.nama_bahan} (Butuh ${resep.qty_butuh}/unit)</span> <span class="font-black text-slate-500">Stok: ${resep.stok}</span>`;
                listBahan.appendChild(li);
            });

            maxLabel.innerText = `${maxProduksi} Unit`;
            infoDiv.classList.remove('hidden');
            lucide.createIcons();

            if (maxProduksi > 0) {
                inputQty.disabled = false; inputQty.max = maxProduksi;
                btnProses.disabled = false; btnProses.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                inputQty.disabled = true;
                btnProses.disabled = true; btnProses.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
        <?php endif; ?>

        lucide.createIcons();
    </script>
</body>
</html>