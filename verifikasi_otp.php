<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

include 'koneksi.php';

if (!isset($_SESSION['verifikasi_uid'])) {
    header("Location: login_siswa.php");
    exit;
}

$pesan = '';

if (isset($_POST['verifikasi'])) {
    $otp_input = mysqli_real_escape_string($koneksi, trim($_POST['otp_code']));
    $uid_siswa = mysqli_real_escape_string($koneksi, $_SESSION['verifikasi_uid']);
    
    // Waktu WIB sekarang dari PHP
    $waktu_sekarang = date('Y-m-d H:i:s');

    // Cek OTP & Bandingkan waktu kadaluarsa menggunakan jam PHP
    $query = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE uid_rfid = '$uid_siswa' AND otp_code = '$otp_input' AND otp_expires >= '$waktu_sekarang'");

    if ($query && mysqli_num_rows($query) > 0) {
        $siswa = mysqli_fetch_assoc($query);

        $_SESSION['siswa_logged'] = true;
        $_SESSION['siswa_uid']    = $siswa['uid_rfid'];
        $_SESSION['siswa_nama']   = $siswa['nama'];

        // Token 14 hari
        $token_device = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+14 days'));
        
        mysqli_query($koneksi, "UPDATE data_siswa SET session_token = '$token_device', token_expires = '$expires', otp_code = NULL, otp_expires = NULL WHERE uid_rfid = '$uid_siswa'");

        setcookie('siswa_device_token', $token_device, time() + (14 * 86400), "/");

        unset($_SESSION['verifikasi_uid']);

        header("Location: riwayat_siswa.php");
        exit;
    } else {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show'>
                    <i class='bi bi-x-circle-fill me-2'></i>Kode OTP salah atau sudah kadaluarsa!
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
    <title>Verifikasi OTP - SMK Nur El-Falah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #198754 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-box border-0 p-4">
                <div class="text-center mb-4">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Masukkan Kode OTP</h4>
                    <p class="text-muted small mb-0">Kode telah dikirimkan ke Telegram Anda.</p>
                </div>

                <?= $pesan ?>

                <form method="POST">
                    <div class="mb-3">
                        <input type="text" name="otp_code" class="form-control form-control-lg text-center fw-bold fs-3" placeholder="6 Digit Kode" maxlength="6" required autocomplete="off" autofocus>
                    </div>

                    <button type="submit" name="verifikasi" class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm mb-3">
                        <i class="bi bi-check-circle-fill me-1"></i> Verifikasi & Masuk
                    </button>
                </form>

                <div class="text-center">
                    <a href="login_siswa.php" class="text-decoration-none small text-muted">
                        <i class="bi bi-arrow-left"></i> Batal / Kirim Ulang OTP
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>