<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged']) && !isset($_SESSION['guru_logged'])) {
    header("Location: index.php");
    exit;
}

$tgl_awal  = isset($_GET['tgl_awal']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_awal']) : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_akhir']) : date('Y-m-d');

$filename = "Rekap_Absensi_" . $tgl_awal . "_sd_" . $tgl_akhir . ".xls";

// Header untuk Export ke Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Cache-Control: max-age=0");

// Query Data Absensi
$query_absen = mysqli_query($koneksi, "
    SELECT a.*, s.nama, s.nis, s.nisn, s.kelas, s.jurusan 
    FROM log_absensi a
    JOIN data_siswa s ON (a.uid_rfid = s.uid_rfid OR a.uid_rfid = s.nis)
    WHERE a.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ORDER BY a.tanggal ASC, a.waktu_tap ASC
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        th, td {
            border: 1px solid #000000;
            padding: 8px 10px;
            font-size: 10pt;
            vertical-align: middle;
        }
        th {
            background-color: #198754;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            font-size: 11pt;
            height: 30px;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        /* Format Teks MSO agar NIS/NISN tidak berubah jadi ilmiah (2,41E+11) */
        .str-format {
            mso-number-format: "\@";
            text-align: center;
        }
        .title-main {
            font-size: 16pt;
            font-weight: bold;
            color: #198754;
        }
        .title-sub {
            font-size: 12pt;
            font-weight: bold;
            color: #333333;
        }
        .title-period {
            font-size: 10pt;
            color: #555555;
        }
    </style>
</head>
<body>

<table>
    <!-- KOP HEADER TANPA LOGO (RATA TENGAH SIMETRIS) -->
    <tr>
        <td colspan="9" style="border: none; text-align: center; vertical-align: middle;">
            <span class="title-main">SMK NUR EL-FALAH</span>
        </td>
    </tr>
    <tr>
        <td colspan="9" style="border: none; text-align: center; vertical-align: middle;">
            <span class="title-sub">LAPORAN REKAPITULASI ABSENSI SISWA</span>
        </td>
    </tr>
    <tr>
        <td colspan="9" style="border: none; text-align: center; vertical-align: middle;">
            <span class="title-period">Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></span>
        </td>
    </tr>
    
    <!-- GARIS PEMBATAS KOP -->
    <tr>
        <td colspan="9" style="border: none; border-bottom: 3px double #198754; height: 5px;"></td>
    </tr>
    <tr>
        <td colspan="9" style="border: none; height: 10px;"></td>
    </tr>

    <!-- HEADER TABEL -->
    <thead>
        <tr>
            <th width="40">No</th>
            <th width="90">Tanggal</th>
            <th width="80">Waktu Tap</th>
            <th width="100">NISN</th>
            <th width="120">NIS</th>
            <th width="220">Nama Siswa</th>
            <th width="70">Kelas</th>
            <th width="90">Jurusan</th>
            <th width="90">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if ($query_absen && mysqli_num_rows($query_absen) > 0) {
            $no = 1;
            while ($row = mysqli_fetch_assoc($query_absen)) {
        ?>
        <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
            <td class="text-center"><?= substr($row['waktu_tap'], 0, 5) ?></td>
            
            <td class="str-format"><?= $row['nisn'] ?></td>
            <td class="str-format"><?= $row['nis'] ?></td>
            
            <td class="text-left"><?= htmlspecialchars($row['nama']) ?></td>
            <td class="text-center"><?= htmlspecialchars($row['kelas']) ?></td>
            <td class="text-center"><?= htmlspecialchars($row['jurusan']) ?></td>
            <td class="text-center"><?= htmlspecialchars($row['status']) ?></td>
        </tr>
        <?php 
            }
        } else {
            echo "<tr><td colspan='9' class='text-center' style='padding: 15px;'>Tidak ada data absensi pada periode tanggal ini.</td></tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>