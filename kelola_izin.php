<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'koneksi.php';

if (!isset($_SESSION['admin_logged']) && !isset($_SESSION['guru_logged'])) {
    header("Location: index.php");
    exit;
}

$pesan = '';

// KETIKA ADMIN/GURU MENGUBAH STATUS (SETUJUI / TOLAK)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_izin = intval($_GET['id']);
    $action  = mysqli_real_escape_string($koneksi, $_GET['action']);

    $q_detail = mysqli_query($koneksi, "SELECT p.*, s.uid_rfid FROM pengajuan_izin p JOIN data_siswa s ON p.nis = s.nis WHERE p.id = '$id_izin'");
    
    if ($q_detail && mysqli_num_rows($q_detail) > 0) {
        $d = mysqli_fetch_assoc($q_detail);
        $nis_siswa = $d['nis'];
        $uid_siswa = !empty($d['uid_rfid']) ? $d['uid_rfid'] : $nis_siswa;

        if ($action == 'setujui') {
            mysqli_query($koneksi, "UPDATE pengajuan_izin SET status = 'Disetujui' WHERE id = '$id_izin'");

            // Loop untuk setiap hari pada rentang tgl_mulai s/d tgl_selesai
            $tgl_curr = strtotime($d['tgl_mulai']);
            $tgl_end  = strtotime($d['tgl_selesai']);

            while ($tgl_curr <= $tgl_end) {
                $tgl_str = date('Y-m-d', $tgl_curr);
                $status_absen = $d['jenis']; // Sakit atau Izin
                $ket_absen    = "Pengajuan " . $d['jenis'] . ": " . $d['keterangan'];

                // Hapus atau Update jika sudah ada log di tanggal tsb, lalu masukkan log baru
                mysqli_query($koneksi, "DELETE FROM log_absensi WHERE (uid_rfid = '$uid_siswa' OR uid_rfid = '$nis_siswa') AND tanggal = '$tgl_str'");
                mysqli_query($koneksi, "INSERT INTO log_absensi (uid_rfid, tanggal, waktu_tap, status, keterangan) 
                                        VALUES ('$uid_siswa', '$tgl_str', '00:00:00', '$status_absen', '$ket_absen')");

                $tgl_curr = strtotime("+1 day", $tgl_curr);
            }

            $pesan = "<div class='alert alert-success alert-dismissible fade show small'>Pengajuan izin berhasil disetujui & otomatis dicatat ke log absensi!</div>";
        } elseif ($action == 'tolak') {
            mysqli_query($koneksi, "UPDATE pengajuan_izin SET status = 'Ditolak' WHERE id = '$id_izin'");
            $pesan = "<div class='alert alert-danger alert-dismissible fade show small'>Pengajuan izin telah ditolak.</div>";
        }
    }
}

// Ambil Semua Pengajuan Izin
$query_list = mysqli_query($koneksi, "
    SELECT p.*, s.nama, s.kelas, s.jurusan 
    FROM pengajuan_izin p 
    JOIN data_siswa s ON p.nis = s.nis 
    ORDER BY FIELD(p.status, 'Pending', 'Disetujui', 'Ditolak'), p.tgl_pengajuan DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Izin / Sakit - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
            <img src="logo.png" alt="Logo" style="height: 35px;" class="me-2">
            SMK Nur El-Falah
        </a>
        <div class="ms-auto">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm fw-semibold rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold text-primary mb-0"><i class="bi bi-card-checklist me-2"></i> Kelola Persetujuan Izin / Sakit Siswa</h5>
        </div>
        <div class="card-body">
            
            <?= $pesan ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>No</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Nama Siswa</th>
                            <th>Kelas & Jurusan</th>
                            <th>Jenis</th>
                            <th>Periode</th>
                            <th>Keterangan</th>
                            <th>Bukti</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($query_list && mysqli_num_rows($query_list) > 0) {
                            $no = 1;
                            while ($r = mysqli_fetch_assoc($query_list)) {
                                $badge = ($r['status'] == 'Disetujui') ? 'bg-success' : (($r['status'] == 'Ditolak') ? 'bg-danger' : 'bg-warning text-dark');
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="text-center small"><?= date('d/m/Y H:i', strtotime($r['tgl_pengajuan'])) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($r['nama']) ?></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= $r['kelas'] ?> - <?= $r['jurusan'] ?></span></td>
                            <td class="text-center"><span class="badge bg-info text-dark"><?= $r['jenis'] ?></span></td>
                            <td class="text-center small fw-semibold"><?= date('d/m/Y', strtotime($r['tgl_mulai'])) ?> s/d <?= date('d/m/Y', strtotime($r['tgl_selesai'])) ?></td>
                            <td class="small"><?= htmlspecialchars($r['keterangan']) ?></td>
                            <td class="text-center">
                                <a href="uploads/surat_izin/<?= $r['foto_bukti'] ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill">
                                    <i class="bi bi-image me-1"></i>Lihat
                                </a>
                            </td>
                            <td class="text-center"><span class="badge <?= $badge ?> rounded-pill px-3"><?= $r['status'] ?></span></td>
                            <td class="text-center">
                                <?php if ($r['status'] == 'Pending') { ?>
                                    <a href="kelola_izin.php?action=setujui&id=<?= $r['id'] ?>" class="btn btn-success btn-sm me-1 rounded-pill fw-semibold" onclick="return confirm('Setujui izin siswa ini?')">
                                        <i class="bi bi-check-circle"></i> Setujui
                                    </a>
                                    <a href="kelola_izin.php?action=tolak&id=<?= $r['id'] ?>" class="btn btn-danger btn-sm rounded-pill fw-semibold" onclick="return confirm('Tolak izin siswa ini?')">
                                        <i class="bi bi-x-circle"></i> Tolak
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted small">Selesai</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='10' class='text-center text-muted py-4'>Belum ada pengajuan izin masuk.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>