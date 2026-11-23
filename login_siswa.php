<?php
session_start();
include 'koneksi.php';

// Masukkan USERNAME BOT TELEGRAM kamu di sini (tanpa tanda @)
$username_bot = "SMKNURELFALLAHOfficialbot"; 

// Cek Cookie 14 Hari
if (!isset($_SESSION['siswa_logged']) && isset($_COOKIE['siswa_device_token'])) {
    $token_cookie = mysqli_real_escape_string($koneksi, $_COOKIE['siswa_device_token']);
    $cek_device = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE session_token = '$token_cookie' AND token_expires > NOW()");
    if ($cek_device && mysqli_num_rows($cek_device) > 0) {
        $siswa = mysqli_fetch_assoc($cek_device);
        $_SESSION['siswa_logged'] = true;
        $_SESSION['siswa_uid'] = $siswa['uid_rfid'];
        $_SESSION['siswa_nama'] = $siswa['nama'];
        header("Location: riwayat_siswa.php");
        exit;
    }
}

if (isset($_SESSION['siswa_logged'])) {
    header("Location: riwayat_siswa.php");
    exit;
}

$pesan = '';
$link_telegram = '';

if (isset($_POST['kirim_otp'])) {
    $nis_input = mysqli_real_escape_string($koneksi, $_POST['nis']);
    $query = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE nis = '$nis_input' OR nisn = '$nis_input' OR no_telp = '$nis_input'");

    if ($query && mysqli_num_rows($query) > 0) {
        $siswa = mysqli_fetch_assoc($query);
        $_SESSION['verifikasi_uid'] = $siswa['uid_rfid'];

        // Buat Link Deep Link Telegram khusus untuk siswa ini
        $link_telegram = "https://t.me/" . $username_bot . "?start=" . $siswa['nis'];
    } else {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show'>
                    <i class='bi bi-x-circle-fill me-2'></i>NIS / No. HP tidak ditemukan!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Siswa - SMK Nur El-Falah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #0d6efd 0%, #198754 100%); min-height: 100vh; display: flex; align-items: center; }
        .card-login { border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-login border-0 p-4 bg-white">
                <div class="text-center mb-4">
                    <img src="logo.png" alt="Logo SMK" style="width: 80px;" class="mb-2">
                    <h4 class="fw-bold text-dark">Portal Absensi Siswa</h4>
                    <p class="text-muted small">SMK Nur El-Falah</p>
                </div>

                <?= $pesan ?>

                <?php if (!empty($link_telegram)): ?>
                    <div class="alert alert-success text-center p-3 mb-3">
                        <i class="bi bi-telegram fs-1 text-success d-block mb-2"></i>
                        <h6 class="fw-bold">Langkah Terakhir!</h6>
                        <p class="small text-muted mb-3">Klik tombol di bawah untuk membuka Telegram dan menerima kode OTP otomatis.</p>
                        <a href="<?= $link_telegram ?>" target="_blank" class="btn btn-success fw-bold w-100 py-2 rounded-pill mb-2">
                            <i class="bi bi-send-fill me-1"></i> Buka Telegram & Dapatkan OTP
                        </a>
                        <a href="verifikasi_otp.php" class="btn btn-outline-primary btn-sm w-100 rounded-pill mt-2">
                            Sudah dapat OTP? Masukkan Kode di Sini <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Masukkan NIS / NISN / No. HP</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                                <input type="text" name="nis" class="form-control" placeholder="Contoh: 241091900185" required autocomplete="off">
                            </div>
                        </div>

                        <button type="submit" name="kirim_otp" class="btn btn-success w-100 py-2 fw-bold rounded-pill shadow-sm">
                            <i class="bi bi-shield-lock-fill me-1"></i> Minta Kode OTP
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>