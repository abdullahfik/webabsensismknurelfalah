<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'koneksi.php';

// Auto-Login via Cookie jika Session Belum Ada
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

if (!isset($_SESSION['siswa_logged']) || $_SESSION['siswa_logged'] !== true) {
    header("Location: index.php");
    exit;
}

$nis_siswa = $_SESSION['siswa_nis'];
$pesan = '';

// Proses Simpan Pengajuan Izin
if (isset($_POST['kirim_izin'])) {
    $jenis       = mysqli_real_escape_string($koneksi, $_POST['jenis']);
    $tgl_mulai   = mysqli_real_escape_string($koneksi, $_POST['tgl_mulai']);
    $tgl_selesai = mysqli_real_escape_string($koneksi, $_POST['tgl_selesai']);
    $keterangan  = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    // Validasi & Upload Foto Bukti Surat
    $nama_file = $_FILES['foto_bukti']['name'];
    $tmp_name  = $_FILES['foto_bukti']['tmp_name'];
    $ukuran    = $_FILES['foto_bukti']['size'];
    $ext       = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
    
    $ext_boleh = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($ext, $ext_boleh)) {
        if ($ukuran <= 5242880) { // Maksimal 5MB
            $nama_foto_baru = 'izin_' . $nis_siswa . '_' . time() . '.' . $ext;
            $target_dir = 'uploads/surat_izin/';

            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (move_uploaded_file($tmp_name, $target_dir . $nama_foto_baru)) {
                $query_simpan = "INSERT INTO pengajuan_izin (nis, jenis, tgl_mulai, tgl_selesai, keterangan, foto_bukti, status) 
                                 VALUES ('$nis_siswa', '$jenis', '$tgl_mulai', '$tgl_selesai', '$keterangan', '$nama_foto_baru', 'Pending')";
                
                if (mysqli_query($koneksi, $query_simpan)) {
                    $pesan = "<div class='alert alert-success alert-dismissible fade show small' role='alert'>
                                <i class='bi bi-check-circle-fill me-1'></i> Pengajuan berhasil dikirim! Menunggu persetujuan guru/admin.
                                <button type='button' class='btn-close py-2' data-bs-dismiss='alert'></button>
                              </div>";
                } else {
                    $pesan = "<div class='alert alert-danger small'>Gagal menyimpan data ke database.</div>";
                }
            } else {
                $pesan = "<div class='alert alert-danger small'>Gagal mengunggah foto bukti. Cek folder uploads/surat_izin.</div>";
            }
        } else {
            $pesan = "<div class='alert alert-warning small'>Ukuran file foto maksimal 5 MB.</div>";
        }
    } else {
        $pesan = "<div class='alert alert-warning small'>Format file harus berupa gambar (JPG, JPEG, PNG, WEBP).</div>";
    }
}

// Ambil Riwayat Pengajuan Izin Siswa
$query_riwayat = mysqli_query($koneksi, "SELECT * FROM pengajuan_izin WHERE nis = '$nis_siswa' ORDER BY tgl_pengajuan DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengajuan Izin / Sakit - Portal Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; }
        @media (max-width: 576px) {
            .container-mobile { padding-left: 12px; padding-right: 12px; }
            .card-responsive { padding: 1rem !important; }
        }
    </style>
</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm mb-3 mb-md-4 sticky-top">
    <div class="container container-mobile">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="riwayat_siswa.php">
            <img src="logo.png" alt="Logo" style="height: 32px;" class="me-2">
            SMK Nur El-Falah
        </a>
        <div class="d-flex align-items-center ms-auto">
            <a href="riwayat_siswa.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
</nav>

<div class="container container-mobile mb-5">
    <div class="row">
        
        <!-- FORM PENGAJUAN -->
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm p-3 p-md-4 bg-white rounded-3 card-responsive">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-plus-fill me-2 text-success"></i>Form Pengajuan Izin / Sakit</h5>
                
                <?= $pesan ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Jenis Pengajuan</label>
                        <select name="jenis" class="form-select" required>
                            <option value="Sakit">Sakit</option>
                            <option value="Izin">Izin</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Dari Tanggal</label>
                            <input type="date" name="tgl_mulai" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Sampai Tanggal</label>
                            <input type="date" name="tgl_selesai" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Keterangan / Alasan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Demam tinggi / Ada urusan keluarga" required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary small">Upload Foto Surat Bukti (JPG/PNG)</label>
                        <input type="file" name="foto_bukti" class="form-control" accept="image/*" required>
                        <div class="form-text small">Foto Surat Dokter (jika sakit) atau Surat Keterangan Orang Tua.</div>
                    </div>

                    <button type="submit" name="kirim_izin" class="btn btn-success w-100 py-2 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-send-fill me-1"></i> Kirim Pengajuan
                    </button>
                </form>
            </div>
        </div>

        <!-- RIWAYAT PENGAJUAN -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-3 p-md-4 bg-white rounded-3 card-responsive">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Pengajuan</h5>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle small text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Periode</th>
                                <th>Bukti</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($query_riwayat && mysqli_num_rows($query_riwayat) > 0) {
                                while ($row = mysqli_fetch_assoc($query_riwayat)) {
                                    $badge = ($row['status'] == 'Disetujui') ? 'bg-success' : (($row['status'] == 'Ditolak') ? 'bg-danger' : 'bg-warning text-dark');
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['tgl_pengajuan'])) ?></td>
                                <td><span class="badge bg-info text-dark fw-bold"><?= $row['jenis'] ?></span></td>
                                <td><?= date('d/m', strtotime($row['tgl_mulai'])) ?> - <?= date('d/m/Y', strtotime($row['tgl_selesai'])) ?></td>
                                <td>
                                    <a href="uploads/surat_izin/<?= $row['foto_bukti'] ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill">
                                        <i class="bi bi-image me-1"></i>Lihat
                                    </a>
                                </td>
                                <td><span class="badge <?= $badge ?> rounded-pill px-3"><?= $row['status'] ?></span></td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center text-muted py-4'>Belum ada riwayat pengajuan izin.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>