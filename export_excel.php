<?php
require_once 'Database.php';
require_once 'Production.php';

$db = new Database();
$production = new Production($db);
$data = $production->getOutboundList();

$filename = "Laporan_Barang_Keluar_" . date('Y-m-d') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<meta charset="UTF-8">
<style>
    .title { font-family: 'Arial'; font-size: 18pt; font-weight: bold; color: #1e40af; text-align: center; }
    .table { border-collapse: collapse; width: 100%; font-family: 'Arial'; }
    .table th { background-color: #1e40af; color: #ffffff; padding: 12px; border: 1px solid #ffffff; text-transform: uppercase; font-size: 10pt; }
    .table td { padding: 10px; border: 1px solid #e2e8f0; font-size: 10pt; }
    .row-even { background-color: #f8fafc; }
    .qty-cell { font-weight: bold; color: #059669; text-align: center; }
    .date-cell { color: #64748b; font-style: italic; }
</style>

<div class="title">LAPORAN RIWAYAT BARANG KELUAR</div>
<div style="text-align: center; margin-bottom: 20px;">Dicetak pada: <?php echo date('d M Y H:i'); ?></div>

<table class="table">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Barang</th>
            <th>Jumlah (Qty)</th>
            <th>Tanggal Keluar</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        foreach($data as $row): 
            $class = ($no % 2 == 0) ? 'row-even' : '';
        ?>
        <tr class="<?php echo $class; ?>">
            <td style="text-align: center;"><?php echo $no++; ?></td>
            <td><?php echo strtoupper($row['nama_barang']); ?></td>
            <td class="qty-cell"><?php echo $row['qty']; ?> Unit</td>
            <td class="date-cell"><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>