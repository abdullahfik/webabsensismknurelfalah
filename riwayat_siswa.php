<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'koneksi.php';

// 1. CEK AUTO-LOGIN VIA COOKIE DEVICE TOKEN JIKA SESSION BELUM ADA[cite: 8]
if (!isset($_SESSION['siswa_logged']) && isset($_COOKIE['siswa_device_token']) && !empty($_COOKIE['siswa_device_token'])) {
    $token_cookie = mysqli_real_escape_string($koneksi, $_COOKIE['siswa_device_token']);
    $waktu_sekarang = date('Y-m-d H:i:s');
    
    $cek_device = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE session_token = '$token_cookie' AND token_expires >= '$waktu_sekarang'");
    
    if ($cek_device && mysqli_num_rows($cek_device) > 0) {
        $siswa = mysqli_fetch_assoc($cek_device);
        $_SESSION['siswa_logged'] = true;
        $_SESSION['siswa_nis']    = $siswa['nis'];
        $_SESSION['siswa_nama']   = $siswa['nama'];
    }
}

// 2. JIKA TETAP TIDAK ADA SESSION LOGIN, ALIHKAN KE INDEX.PHP[cite: 8]
if (!isset($_SESSION['siswa_logged']) || $_SESSION['siswa_logged'] !== true) {
    header("Location: index.php");
    exit;
}

// 3. AMBIL IDENTIFIER SISWA DARI SESSION[cite: 8]
$identifier = isset($_SESSION['siswa_nis']) ? $_SESSION['siswa_nis'] : (isset($_SESSION['siswa_uid']) ? $_SESSION['siswa_uid'] : '');
$identifier = mysqli_real_escape_string($koneksi, $identifier);

// 4. QUERY DATA PROFIL SISWA DARI DATABASE[cite: 8]
$query_siswa = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE nis = '$identifier' OR nisn = '$identifier' OR (uid_rfid = '$identifier' AND uid_rfid IS NOT NULL AND uid_rfid != '')");
$data_siswa = mysqli_fetch_assoc($query_siswa);

if (!$data_siswa) {
    header("Location: logout.php");
    exit;
}

$nis_siswa     = $data_siswa['nis'];
$nisn_siswa    = $data_siswa['nisn'];
$uid_siswa     = isset($data_siswa['uid_rfid']) && !empty($data_siswa['uid_rfid']) ? $data_siswa['uid_rfid'] : $data_siswa['nis'];
$kelas_siswa   = $data_siswa['kelas'];
$jurusan_siswa = $data_siswa['jurusan'];

// ================= LOGIKA OTOMASI CEK FOTO BERDASARKAN NIS / NISN =================
$foto_path = '';
$ekstensi = ['jpg', 'jpeg', 'png', 'webp'];

foreach ($ekstensi as $ext) {
    if (!empty($nis_siswa) && file_exists('uploads/foto_siswa/' . $nis_siswa . '.' . $ext)) {
        $foto_path = 'uploads/foto_siswa/' . $nis_siswa . '.' . $ext;
        break;
    } elseif (!empty($nisn_siswa) && file_exists('uploads/foto_siswa/' . $nisn_siswa . '.' . $ext)) {
        $foto_path = 'uploads/foto_siswa/' . $nisn_siswa . '.' . $ext;
        break;
    }
}

// Fallback jika ada custom filename di kolom database
if (empty($foto_path) && !empty($data_siswa['foto']) && file_exists('uploads/foto_siswa/' . $data_siswa['foto'])) {
    $foto_path = 'uploads/foto_siswa/' . $data_siswa['foto'];
}

// AMBIL JADWAL PELAJARAN SISWA[cite: 8]
$query_jadwal = mysqli_query($koneksi, "SELECT * FROM jadwal_pelajaran WHERE kelas = '$kelas_siswa' AND jurusan = '$jurusan_siswa' ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), jam_mulai ASC");

// HITUNG STATISTIK ABSEN[cite: 8]
$q_hadir = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_absensi WHERE (uid_rfid = '$uid_siswa' OR uid_rfid = '$nis_siswa') AND status = 'Hadir'");
$hadir = ($q_hadir) ? mysqli_fetch_assoc($q_hadir)['total'] : 0;

$q_terlambat = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_absensi WHERE (uid_rfid = '$uid_siswa' OR uid_rfid = '$nis_siswa') AND status = 'Terlambat'");
$terlambat = ($q_terlambat) ? mysqli_fetch_assoc($q_terlambat)['total'] : 0;

$q_sakit = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_absensi WHERE (uid_rfid = '$uid_siswa' OR uid_rfid = '$nis_siswa') AND status = 'Sakit'");
$sakit = ($q_sakit) ? mysqli_fetch_assoc($q_sakit)['total'] : 0;

$q_izin = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_absensi WHERE (uid_rfid = '$uid_siswa' OR uid_rfid = '$nis_siswa') AND status = 'Izin'");
$izin = ($q_izin) ? mysqli_fetch_assoc($q_izin)['total'] : 0;

// RIWAYAT ABSENSI[cite: 8]
$query_absen = mysqli_query($koneksi, "SELECT * FROM log_absensi WHERE (uid_rfid = '$uid_siswa' OR uid_rfid = '$nis_siswa') ORDER BY tanggal DESC, waktu_tap DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Siswa - <?= htmlspecialchars($data_siswa['nama']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm mb-4">
    <div class="container">
        <!-- BRAND LOGO -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
            <img src="logo.png" alt="Logo" style="height: 35px;" class="me-2">
            SMK Nur El-Falah
        </a>

        <!-- AREA KANAN NAVBAR -->
        <div class="d-flex align-items-center ms-auto gap-2">
            <!-- Tombol Ajukan Izin/Sakit -->
            <a href="izin_siswa.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-semibold text-dark">
                <i class="bi bi-file-earmark-plus-fill me-1"></i> Ajukan Izin/Sakit
            </a>

            <!-- Nama Siswa -->
            <span class="text-white d-none d-md-inline ms-1 me-2">
                Halo, <b><?= htmlspecialchars($data_siswa['nama']) ?></b>
            </span>

            <!-- Tombol Logout -->
            <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill fw-semibold">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container mb-5">
    <div class="row">
        <!-- Profil & Statistik -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm p-4 mb-3 bg-white rounded-3">
                <div class="text-center mb-3">
                    
                    <!-- FOTO PROFIL SISWA (DETEKSI NIS OTOMATIS) -->
                    <div class="mb-2">
                        <?php if (!empty($foto_path)) { ?>
                            <img src="<?= $foto_path ?>" alt="Foto Profil" class="rounded-circle img-thumbnail shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                        <?php } else { ?>
                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 90px; height: 90px;">
                                <i class="bi bi-person-fill fs-1"></i>
                            </div>
                        <?php } ?>
                    </div>

                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($data_siswa['nama']) ?></h5>
                    <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($data_siswa['kelas']) ?> - <?= htmlspecialchars($data_siswa['jurusan']) ?></span>
                </div>
                <hr>
                <div class="small">
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">NIS:</span><span class="fw-semibold"><?= htmlspecialchars($data_siswa['nis']) ?></span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">NISN:</span><span class="fw-semibold"><?= htmlspecialchars($data_siswa['nisn']) ?></span></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart-fill me-2 text-success"></i>Statistik Kehadiran</h6>
                <div style="max-width: 220px; margin: 0 auto;">
                    <canvas id="grafikAbsen"></canvas>
                </div>
            </div>
        </div>

        <!-- Jadwal Pelajaran & Catatan Absen -->
        <div class="col-lg-8">
            <!-- TAB NAVIGASI -->
            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold rounded-pill px-4" id="tab-jadwal" data-bs-toggle="pill" data-bs-target="#content-jadwal" type="button"><i class="bi bi-calendar-week me-1"></i> Jadwal Pelajaran</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold rounded-pill px-4" id="tab-absen" data-bs-toggle="pill" data-bs-target="#content-absen" type="button"><i class="bi bi-clock-history me-1"></i> Catatan Absensi</button>
                </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                <!-- KONTEN JADWAL -->
                <div class="tab-pane fade show active" id="content-jadwal">
                    <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-book-fill me-2 text-primary"></i>Jadwal Pelajaran Kelas <?= htmlspecialchars($kelas_siswa) ?> - <?= htmlspecialchars($jurusan_siswa) ?></h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Hari</th>
                                        <th>Jam</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Guru / Pengajar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if($query_jadwal && mysqli_num_rows($query_jadwal) > 0) {
                                        while($j = mysqli_fetch_assoc($query_jadwal)) {
                                    ?>
                                    <tr>
                                        <td><span class="badge bg-info text-dark fw-bold"><?= $j['hari'] ?></span></td>
                                        <td class="small fw-semibold"><?= substr($j['jam_mulai'],0,5) ?> - <?= substr($j['jam_selesai'],0,5) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($j['mapel']) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($j['nama_guru']) ?></td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center text-muted py-4'>Belum ada jadwal pelajaran untuk kelas/jurusan ini.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- KONTEN ABSENSI -->
                <div class="tab-pane fade" id="content-absen">
                    <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-check-circle-fill me-2 text-success"></i>Riwayat Kehadiran</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Waktu Tap</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if($query_absen && mysqli_num_rows($query_absen) > 0) {
                                        $no = 1;
                                        while($row = mysqli_fetch_assoc($query_absen)) {
                                            $badge = ($row['status'] == 'Hadir') ? 'bg-success' : (($row['status'] == 'Terlambat') ? 'bg-danger' : 'bg-warning text-dark');
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                        <td class="fw-bold text-primary"><?= $row['waktu_tap'] ?></td>
                                        <td><span class="badge <?= $badge ?> rounded-pill px-3"><?= $row['status'] ?></span></td>
                                        <td class="small text-muted"><?= htmlspecialchars($row['keterangan']) ?></td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center text-muted py-4'>Belum ada catatan absensi.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('grafikAbsen').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Hadir', 'Terlambat', 'Sakit', 'Izin'],
            datasets: [{
                data: [<?= $hadir ?>, <?= $terlambat ?>, <?= $sakit ?>, <?= $izin ?>],
                backgroundColor: ['#198754', '#dc3545', '#ffc107', '#0dcaf0']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>