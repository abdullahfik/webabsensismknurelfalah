<?php
session_start();
// Pastikan yang akses minimal sudah login (bisa login sebagai admin atau sebagai guru)
if (!isset($_SESSION['admin_logged']) && !isset($_SESSION['guru_logged'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$tanggal_hari_ini = date('Y-m-d');
$pesan = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nis = mysqli_real_escape_string($koneksi, $_POST['nis']);
    $status_manual = mysqli_real_escape_string($koneksi, $_POST['status_manual']); // Sakit / Izin
    $keterangan_manual = mysqli_real_escape_string($koneksi, $_POST['keterangan_manual']);
    $waktu = date('H:i:s');

    // Cari UID siswa berdasarkan NIS yang dimasukkan
    $cek_siswa = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE nis = '$nis'");
    if (mysqli_num_rows($cek_siswa) > 0) {
        $data = mysqli_fetch_assoc($cek_siswa);
        $uid = $data['uid_rfid'];
        $nama = $data['nama'];

        // Cek apakah siswa sudah absen hari ini
        $cek_absen = mysqli_query($koneksi, "SELECT * FROM log_absensi WHERE uid_rfid = '$uid' AND tanggal = '$tanggal_hari_ini'");
        
        if (mysqli_num_rows($cek_absen) > 0) {
            // Jika sudah terlanjur absen, update statusnya menjadi Sakit/Izin
            $update = mysqli_query($koneksi, "UPDATE log_absensi SET status = '$status_manual', keterangan = '$keterangan_manual' WHERE uid_rfid = '$uid' AND tanggal = '$tanggal_hari_ini'");
            if ($update) {
                $pesan = "<div class='alert alert-success'>Status kehadiran siswa <b>$nama</b> berhasil diperbarui menjadi <b>$status_manual</b>!</div>";
            }
        } else {
            // Jika belum absen sama sekali hari ini, masukkan data baru
            $insert = mysqli_query($koneksi, "INSERT INTO log_absensi (uid_rfid, tanggal, waktu_tap, status, keterangan) VALUES ('$uid', '$tanggal_hari_ini', '$waktu', '$status_manual', '$keterangan_manual')");
            if ($insert) {
                $pesan = "<div class='alert alert-success'>Berhasil mencatat siswa <b>$nama</b> dengan status <b>$status_manual</b>!</div>";
            }
        }
    } else {
        $pesan = "<div class='alert alert-danger'>NIS tidak ditemukan di database! Pastikan NIS benar.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Kehadiran Manual - SMK Nur El-Falah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="index.php">Absensi Digital - SMK Nur El-Falah</a>
    <a href="index.php" class="btn btn-outline-light btn-sm">Kembali ke Dashboard</a>
  </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">📝 Form Input Kehadiran Manual (Sakit / Izin)</h5>
                </div>
                <div class="card-body">
                    <?= $pesan ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nomor Induk Siswa (NIS)</label>
                            <input type="text" name="nis" class="form-control" placeholder="Masukkan NIS Siswa..." required>
                            <small class="text-muted">Contoh: 241090200185</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status Kehadiran</label>
                            <select name="status_manual" class="form-select" required>
                                <option value="Sakit">Sakit</option>
                                <option value="Izin">Izin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Keterangan / Alasan</label>
                            <input type="text" name="keterangan_manual" class="form-control" placeholder="Contoh: Surat dokter / Urusan keluarga" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Simpan Kehadiran</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>