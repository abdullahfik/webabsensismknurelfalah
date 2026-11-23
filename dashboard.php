<?php
session_start();
if (!isset($_SESSION['admin_logged']) && !isset($_SESSION['guru_logged'])) {
    header("Location: index.php");
    exit;
}
include 'koneksi.php';

// Ambil data unik Kelas & Jurusan dari database secara otomatis (Dinamis)
$q_kelas   = mysqli_query($koneksi, "SELECT DISTINCT kelas FROM data_siswa WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC");
$q_jurusan = mysqli_query($koneksi, "SELECT DISTINCT jurusan FROM data_siswa WHERE jurusan IS NOT NULL AND jurusan != '' ORDER BY jurusan ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Absensi - SMK Nur El-Falah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<style>
    /* 1. Animasi Halaman Muncul dari Bawah (Slide Up & Fade In) */
    @keyframes slideUpFade {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Menerapkan animasi ke semua kartu/kotak menu */
    .card {
        animation: slideUpFade 0.6s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    /* 2. Animasi Tombol Bergerak Halus Saat Disentuh (Hover) */
    .btn {
        transition: all 0.3s ease-in-out !important;
    }
    .btn:hover {
        transform: translateY(-3px); /* Tombol sedikit terangkat */
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15) !important;
    }
    .btn:active {
        transform: translateY(1px); /* Tombol tertekan ke bawah */
    }

    /* 3. Animasi Kolom Input (Fokus lebih hidup) */
    .form-control, .form-select {
        transition: all 0.3s ease;
    }
    .form-control:focus, .form-select:focus {
        transform: scale(1.02); /* Sedikit membesar saat diketik */
        box-shadow: 0 0 10px rgba(13, 110, 253, 0.25);
        border-color: #0d6efd;
    }

    /* 4. Animasi Baris Tabel Absen */
    .table-hover tbody tr {
        transition: background-color 0.2s ease, transform 0.2s ease;
    }
    .table-hover tbody tr:hover {
        transform: scale(1.01); /* Baris tabel sedikit maju */
        background-color: rgba(13, 110, 253, 0.05) !important;
    }

    /* 5. Transisi Tab Menu (Khusus Halaman Login) */
    .nav-pills .nav-link {
        transition: all 0.3s ease-in-out;
    }
    .nav-pills .nav-link:hover:not(.active) {
        background-color: rgba(25, 135, 84, 0.1);
        transform: translateY(-2px);
    }
</style>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
            <!-- Menampilkan logo dengan tinggi 35px agar pas dengan navbar -->
            <img src="logo.png" alt="Logo SMK" style="height: 35px; width: auto;" class="me-2"> 
            SMK Nur El-Falah
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link active fw-semibold" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                
                <!-- MENU PERSETUJUAN IZIN (BISA DIAKSES ADMIN & GURU) -->
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-warning" href="kelola_izin.php">
                        <i class="bi bi-card-checklist"></i> Persetujuan Izin
                    </a>
                </li>

                <!-- MENU KHUSUS ADMIN -->
                <?php if (isset($_SESSION['admin_logged'])) { ?>
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="tambah_siswa.php"><i class="bi bi-person-plus"></i> Tambah Siswa</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="atur_jadwal.php"><i class="bi bi-calendar-week"></i> Atur Jadwal</a>
                </li>

                <li class="nav-item ms-lg-2">
                    <a href="naik_kelas.php" class="btn btn-warning fw-semibold shadow-sm">
                        <i class="bi bi-arrow-up-circle-fill me-1"></i> Kenaikan Kelas
                    </a>
                </li>
                <?php } ?>
                
                <li class="nav-item ms-lg-3">
                    <a class="btn btn-danger btn-sm fw-bold px-3 rounded-pill" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-table me-2"></i> Rekap Absensi
                        <span class="spinner-grow spinner-grow-sm text-success ms-2" title="Realtime Monitoring Auto-Refresh"></span>
                    </h5>
                </div>
                
                <div class="col-md-8">
                    <div class="d-flex flex-wrap justify-content-md-end gap-2">
                        <!-- FILTER KELAS (DINAMIS SAMA SEPERTI DATABASE) -->
                        <div class="input-group style-select" style="max-width: 170px;">
                            <span class="input-group-text bg-light"><i class="bi bi-layers text-secondary"></i></span>
                            <select id="filterKelas" class="form-select form-select-sm" onchange="loadDataAbsen()">
                                <option value="">Semua Tingkat</option>
                                <?php while($k = mysqli_fetch_assoc($q_kelas)) { ?>
                                    <option value="<?= $k['kelas'] ?>">Kelas <?= $k['kelas'] ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- FILTER JURUSAN -->
                        <div class="input-group style-select" style="max-width: 180px;">
                            <span class="input-group-text bg-light"><i class="bi bi-journal-bookmark text-secondary"></i></span>
                            <select id="filterJurusan" class="form-select form-select-sm" onchange="loadDataAbsen()">
                                <option value="">Semua Jurusan</option>
                                <?php while($j = mysqli_fetch_assoc($q_jurusan)) { ?>
                                    <option value="<?= $j['jurusan'] ?>"><?= $j['jurusan'] ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Tombol Export yang memicu Pop-Up Modal -->
                        <button type="button" class="btn btn-success btn-sm fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalExport">
                            <i class="bi bi-file-earmark-excel"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-primary text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal</th>
                            <th>Waktu Tap</th>
                            <th>NISN / NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas & Jurusan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tabelAbsen">
                        <!-- Data AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- POP-UP MODAL EXPORT -->
<div class="modal fade" id="modalExport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel-fill me-2"></i> Export Data Absensi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="export_excel.php" method="GET">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Tanggal Awal</label>
                        <input type="date" name="tgl_awal" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Pilih Tanggal Akhir</label>
                        <input type="date" name="tgl_akhir" class="form-control" required>
                    </div>
                    
                    <div class="alert alert-info small py-2 mb-0">
                        <i class="bi bi-info-circle me-1"></i> Data yang di-export akan berupa file Excel (XLS) sesuai rentang tanggal yang Anda pilih.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-download me-1"></i> Download Excel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function loadDataAbsen() {
        let kelas = document.getElementById('filterKelas').value;
        let jurusan = document.getElementById('filterJurusan').value;
        fetch('load_data.php?kelas=' + encodeURIComponent(kelas) + '&jurusan=' + encodeURIComponent(jurusan))
            .then(response => response.text())
            .then(data => document.getElementById('tabelAbsen').innerHTML = data)
            .catch(error => console.error('Error loading data:', error));
    }
    document.addEventListener("DOMContentLoaded", function() {
        loadDataAbsen();
        setInterval(loadDataAbsen, 3000);
    });
</script>
</body>
</html>