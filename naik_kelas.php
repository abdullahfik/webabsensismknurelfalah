<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header("Location: index.php");
    exit;
}
include 'koneksi.php';

$pesan = '';

if (isset($_POST['proses_naik'])) {
    $kelas_asal = mysqli_real_escape_string($koneksi, $_POST['kelas_asal']);
    $kelas_tujuan = mysqli_real_escape_string($koneksi, $_POST['kelas_tujuan']);
    $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan']);

    // Jika filter jurusan dipilih (spesifik)
    if (!empty($jurusan)) {
        $query = "UPDATE data_siswa SET kelas = '$kelas_tujuan' WHERE kelas = '$kelas_asal' AND jurusan = '$jurusan'";
    } else {
        // Update seluruh tingkat kelas sekaligus
        $query = "UPDATE data_siswa SET kelas = '$kelas_tujuan' WHERE kelas = '$kelas_asal'";
    }

    if (mysqli_query($koneksi, $query)) {
        $jumlah = mysqli_affected_rows($koneksi);
        $pesan = "<div class='alert alert-success alert-dismissible fade show'>
                    <i class='bi bi-check-circle-fill me-2'></i> <strong>Berhasil!</strong> Sebanyak <b>$jumlah siswa</b> berhasil dipindahkan dari Kelas <b>$kelas_asal</b> ke <b>$kelas_tujuan</b>. Semua kartu RFID tetap terhubung secara otomatis.
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    } else {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show'>
                    <i class='bi bi-exclamation-triangle-fill me-2'></i> Gagal pembaruan: " . mysqli_error($koneksi) . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kenaikan Kelas Massal - SMK Nur El-Falah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
            <i class="bi bi-mortarboard-fill me-2"></i> SMK Nur El-Falah
        </a>
        <a href="dashboard.php" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm border-0 p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary text-white rounded-circle p-3 me-3">
                        <i class="bi bi-arrow-up-circle-fill fs-3"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0">Kenaikan Kelas Massal</h4>
                        <p class="text-muted small mb-0">Pindahkan ratusan siswa sekaligus tanpa perlu mendaftarkan kartu RFID ulang.</p>
                    </div>
                </div>

                <?= $pesan ?>

                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memproses kenaikan kelas ini? Data kelas siswa akan langsung berubah.');">
                    <div class="card bg-light border-0 p-3 mb-3">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-funnel-fill me-1"></i> Pengaturan Kenaikan Kelas</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold small">Dari Kelas (Asal)</label>
                                <select name="kelas_asal" class="form-select fw-bold text-danger" required>
                                    <option value="">-- Pilih Kelas Asal --</option>
                                    <option value="X">Kelas X (10)</option>
                                    <option value="XI">Kelas XI (11)</option>
                                    <option value="XII">Kelas XII (12) / Calon Lulus</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold small">Dinaikkan Ke (Tujuan)</label>
                                <select name="kelas_tujuan" class="form-select fw-bold text-success" required>
                                    <option value="">-- Pilih Kelas Tujuan --</option>
                                    <option value="XI">Kelas XI (11)</option>
                                    <option value="XII">Kelas XII (12)</option>
                                    <option value="LULUS">Alumni / Lulus</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Filter Jurusan & Rombel (Opsional)</label>
                            <select name="jurusan" class="form-select">
                                <option value="">-- Semua Jurusan & Rombel (Proses Semua) --</option>
                                <optgroup label="TKJ">
                                    <option value="TKJ 1">TKJ 1</option>
                                    <option value="TKJ 2">TKJ 2</option>
                                    <option value="TKJ 3">TKJ 3</option>
                                </optgroup>
                                <optgroup label="RPL">
                                    <option value="RPL 1">RPL 1</option>
                                    <option value="RPL 2">RPL 2</option>
                                </optgroup>
                                <optgroup label="TKR">
                                    <option value="TKR 1">TKR 1</option>
                                    <option value="TKR 2">TKR 2</option>
                                </optgroup>
                                <optgroup label="TSM">
                                    <option value="TSM 1">TSM 1</option>
                                    <option value="TSM 2">TSM 2</option>
                                </optgroup>
                                <optgroup label="OTKP">
                                    <option value="OTKP 1">OTKP 1</option>
                                    <option value="OTKP 2">OTKP 2</option>
                                </optgroup>
                                <optgroup label="AKL">
                                    <option value="AKL 1">AKL 1</option>
                                    <option value="AKL 2">AKL 2</option>
                                </optgroup>
                            </select>
                            <div class="form-text small text-muted">*Biarkan kosong jika ingin menaikkan seluruh siswa di tingkat kelas tersebut tanpa memandang jurusan.</div>
                        </div>
                    </div>

                    <div class="alert alert-info small mb-4">
                        <i class="bi bi-info-circle-fill me-1"></i> <b>Urutan yang disarankan setiap tahun ajaran baru:</b>
                        <ol class="mb-0 ps-3 mt-1">
                            <li>Pindahkan kelas <b>XII</b> ke status <b>LULUS</b> terlebih dahulu.</li>
                            <li>Pindahkan kelas <b>XI</b> ke kelas <b>XII</b>.</li>
                            <li>Pindahkan kelas <b>X</b> ke kelas <b>XI</b>.</li>
                            <li>Terakhir, input siswa baru untuk kelas <b>X</b> melalui menu Tambah Siswa.</li>
                        </ol>
                    </div>

                    <button type="submit" name="proses_naik" class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-rocket-takeoff-fill me-1"></i> Proses Kenaikan Kelas Massal
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>