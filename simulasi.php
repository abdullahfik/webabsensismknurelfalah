<?php
include 'koneksi.php';

date_default_timezone_set('Asia/Jakarta');

// Jika tombol simpan ditekan
if (isset($_POST['set_jam'])) {
    $jam_palsu = $_POST['jam_palsu'];
    mysqli_query($koneksi, "UPDATE pengaturan_simulasi SET jam_simulasi = '$jam_palsu', status_simulasi = 'aktif' WHERE id = 1");
    header("Location: simulasi.php?status=sukses");
    exit;
}

// Jika tombol reset/matikan ditekan
if (isset($_GET['reset'])) {
    mysqli_query($koneksi, "UPDATE pengaturan_simulasi SET status_simulasi = 'off' WHERE id = 1");
    header("Location: simulasi.php?status=reset");
    exit;
}

// Ambil status simulasi saat ini dari database
$q = mysqli_query($koneksi, "SELECT * FROM pengaturan_simulasi WHERE id = 1");
$row = mysqli_fetch_assoc($q);
$status_aktif = $row['status_simulasi'];
$jam_aktif = $row['jam_simulasi'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Simulasi Jam Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow border-0 p-4">
                <h4 class="text-center text-primary mb-3">⚙️ Panel Simulasi Jam Absen</h4>
                
                <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                    <div class="alert alert-success text-center">Jam simulasi berhasil diaktifkan!</div>
                <?php elseif (isset($_GET['status']) && $_GET['status'] == 'reset'): ?>
                    <div class="alert alert-secondary text-center">Simulasi dimatikan. Kembali ke jam asli!</div>
                <?php endif; ?>

                <div class="alert <?= ($status_aktif == 'aktif') ? 'alert-warning' : 'alert-info' ?> text-center">
                    Status: <b><?= ($status_aktif == 'aktif') ? 'SIMULASI AKTIF' : 'MENGGUNAKAN JAM ASLI' ?></b><br>
                    Jam yang Dibaca Alat Saat Ini: <br><b class="fs-4"><?= ($status_aktif == 'aktif') ? $jam_aktif : date('H:i:s') ?></b>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Jam Simulasi:</label>
                        <input type="time" step="1" name="jam_palsu" class="form-control" value="08:00:00" required>
                    </div>
                    <button type="submit" name="set_jam" class="btn btn-primary w-100 mb-2">Aktifkan Jam Simulasi</button>
                    <a href="simulasi.php?reset=true" class="btn btn-outline-danger w-100">Matikan Simulasi (Kembali ke Jam Asli)</a>
                </form>
                <div class="text-center mt-3">
                    <a href="dashboard.php" class="text-decoration-none">← Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>