<?php
include 'koneksi.php';

$nisn_cari = isset($_POST['nisn']) ? mysqli_real_escape_string($koneksi, $_POST['nisn']) : '';
$siswa = null;
$stats = null;
$riwayat = null;

if (!empty($nisn_cari)) {
    // Cari data siswa berdasarkan NISN
    $query_siswa = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE nisn = '$nisn_cari'");
    if (mysqli_num_rows($query_siswa) > 0) {
        $siswa = mysqli_fetch_assoc($query_siswa);
        $uid_siswa = $siswa['uid_rfid'];

        $q_hadir = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_absensi WHERE uid_rfid = '$uid_siswa' AND status = 'Hadir'");
        $q_terlambat = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_absensi WHERE uid_rfid = '$uid_siswa' AND status = 'Terlambat'");
        $q_sakit = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_absensi WHERE uid_rfid = '$uid_siswa' AND status = 'Sakit'");
        $q_izin = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_absensi WHERE uid_rfid = '$uid_siswa' AND status = 'Izin'");

        $stats = [
            'hadir' => mysqli_fetch_assoc($q_hadir)['total'],
            'terlambat' => mysqli_fetch_assoc($q_terlambat)['total'],
            'sakit' => mysqli_fetch_assoc($q_sakit)['total'],
            'izin' => mysqli_fetch_assoc($q_izin)['total']
        ];

        $riwayat = mysqli_query($koneksi, "SELECT * FROM log_absensi WHERE uid_rfid = '$uid_siswa' ORDER BY tanggal DESC, waktu_tap DESC LIMIT 5");
    } else {
        $error = "Data siswa dengan NISN $nisn_cari tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Absen Siswa - SMK Nur El-Falah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .card-stat { border-radius: 15px; border: none; transition: 0.3s; }
        .card-stat:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="py-5">
<div class="container">
    <div class="row justify-content-center mb-4">
        <div class="col-md-6 text-center">
            <h2 class="fw-bold text-primary"><i class="bi bi-search me-2"></i>Portal Cek Absen Siswa</h2>
            <p class="text-muted">Masukkan Nomor Induk Siswa Nasional (NISN) untuk melihat rekap kehadiranmu.</p>
        </div>
    </div>

    <!-- Form Pencarian -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 p-3">
                <form method="POST" class="d-flex">
                    <input type="text" name="nisn" class="form-control me-2" placeholder="Masukkan NISN Anda..." value="<?= $nisn_cari ?>" required autofocus>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-search"></i> Cari</button>
                </form>
                <?php if (isset($error)): ?>
                    <div class="text-danger small mt-2 fw-semibold text-center"><i class="bi bi-exclamation-circle"></i> <?= $error ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hasil Pencarian -->
    <?php if ($siswa): ?>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                        <i class="bi bi-person-badge fs-1"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold"><?= $siswa['nama'] ?></h4>
                        <p class="mb-0 text-muted">NISN: <?= $siswa['nisn'] ?> | NIS: <?= $siswa['nis'] ?> | Kelas: <?= $siswa['kelas'] ?></p>
                    </div>
                </div>
            </div>

            <!-- Statistik -->
            <div class="row text-center mb-4 g-3">
                <div class="col-6 col-md-3">
                    <div class="card card-stat shadow-sm bg-success text-white p-3">
                        <h2 class="fw-bold mb-0"><?= $stats['hadir'] ?></h2>
                        <small>Hadir Tepat Waktu</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-stat shadow-sm bg-danger text-white p-3">
                        <h2 class="fw-bold mb-0"><?= $stats['terlambat'] ?></h2>
                        <small>Terlambat</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-stat shadow-sm bg-warning text-dark p-3">
                        <h2 class="fw-bold mb-0"><?= $stats['sakit'] ?></h2>
                        <small>Sakit</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-stat shadow-sm bg-info text-dark p-3">
                        <h2 class="fw-bold mb-0"><?= $stats['izin'] ?></h2>
                        <small>Izin</small>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Terakhir -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-clock-history me-2"></i> 5 Riwayat Absen Terakhir</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Jam Tap</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($riwayat) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($riwayat)): ?>
                                    <tr>
                                        <td><?= $r['tanggal'] ?></td>
                                        <td><?= $r['waktu_tap'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= ($r['status'] == 'Hadir') ? 'success' : (($r['status'] == 'Terlambat') ? 'danger' : 'warning text-dark') ?>">
                                                <?= $r['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted">Belum ada riwayat absen.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center mt-5">
        <a href="index.php" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Halaman Login</a>
    </div>
</div>
</body>
</html>