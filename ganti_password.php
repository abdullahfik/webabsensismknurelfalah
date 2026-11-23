<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'koneksi.php';

$token_bot = "8983955338:AAGpnyJSXwTjzP-uPpE0VjW_0prlSvPHwfk";
$pesan = '';
$step = 1;

if (isset($_GET['first']) && isset($_SESSION['reset_nis'])) {
    $pesan = "<div class='alert alert-info small text-center py-2 mb-3'>Password kamu masih bawaan (123456). Silakan minta OTP untuk membuat password baru!</div>";
}

if (isset($_POST['kirim_otp'])) {
    $nis_input = mysqli_real_escape_string($koneksi, trim($_POST['nis_nisn']));

    $query = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE nis = '$nis_input' OR nisn = '$nis_input'");
    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);

        if (!empty($data['telegram_id'])) {
            $otp = rand(100000, 999999);
            $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $_SESSION['reset_nis']  = $data['nis'];
            $_SESSION['reset_otp']  = $otp;
            $_SESSION['reset_nama'] = $data['nama'];

            mysqli_query($koneksi, "UPDATE data_siswa SET otp_code = '$otp', otp_expires = '$otp_expires' WHERE nis = '{$data['nis']}'");

            $text = "🔑 *KODE VERIFIKASI GANTI PASSWORD*\n\n";
            $text .= "Halo *" . $data['nama'] . "*,\n";
            $text .= "Kode OTP kamu adalah: *$otp*\n\n";
            $text .= "_Kode berlaku selama 15 menit. Jangan berikan kepada siapapun!_";

            $url = "https://api.telegram.org/bot$token_bot/sendMessage?chat_id={$data['telegram_id']}&text=" . urlencode($text) . "&parse_mode=Markdown";
            @file_get_contents($url);

            $step = 2;
            $pesan = "<div class='alert alert-success small text-center py-2 mb-3'>Kode OTP telah dikirim ke Telegram <b>{$data['nama']}</b>!</div>";
        } else {
            $pesan = "<div class='alert alert-danger small text-center py-2 mb-3'>Telegram kamu belum terhubung! Silakan hubungkan Telegram terlebih dahulu.</div>";
        }
    } else {
        $pesan = "<div class='alert alert-danger small text-center py-2 mb-3'>NIS / NISN tidak ditemukan!</div>";
    }
} 
elseif (isset($_POST['simpan_password'])) {
    $step = 2;
    $otp_input  = trim($_POST['otp']);
    $pass_baru  = mysqli_real_escape_string($koneksi, trim($_POST['password_baru']));
    $nis_siswa  = $_SESSION['reset_nis'];
    $waktu_sekarang = date('Y-m-d H:i:s');

    $cek_otp = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE nis = '$nis_siswa' AND otp_code = '$otp_input' AND otp_expires >= '$waktu_sekarang'");

    if ($cek_otp && mysqli_num_rows($cek_otp) > 0) {
        $siswa = mysqli_fetch_assoc($cek_otp);

        $token_device  = bin2hex(random_bytes(32));
        $token_expires = date('Y-m-d H:i:s', strtotime('+14 days'));

        mysqli_query($koneksi, "UPDATE data_siswa SET password = '$pass_baru', session_token = '$token_device', token_expires = '$token_expires', otp_code = NULL, otp_expires = NULL WHERE nis = '$nis_siswa'");

        setcookie('siswa_device_token', $token_device, time() + (14 * 86400), "/", "", false, true);

        $_SESSION['siswa_logged'] = true;
        $_SESSION['siswa_nis']    = $siswa['nis'];
        $_SESSION['siswa_nama']   = $siswa['nama'];

        unset($_SESSION['reset_nis']);
        unset($_SESSION['reset_otp']);

        echo "<script>
                alert('Password berhasil diperbarui! Anda otomatis masuk.');
                window.location.href='riwayat_siswa.php';
              </script>";
        exit;
    } else {
        $pesan = "<div class='alert alert-danger small text-center py-2 mb-3'>Kode OTP salah atau sudah kadaluarsa (lebih dari 15 menit)!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ganti Password - SMK Nur El-Falah</title>
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
            border-radius: 1.25rem; 
        }
        @media (max-width: 576px) {
            .container-mobile {
                padding-left: 15px;
                padding-right: 15px;
            }
            .card-box {
                padding: 1.25rem !important;
            }
        }
    </style>
</head>
<body>

<div class="container container-mobile py-4 my-auto">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-9 col-md-7 col-lg-5 col-xl-4">
            <div class="card card-box border-0 shadow-lg p-3 p-sm-4">
                
                <div class="text-center mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 55px; height: 55px;">
                        <i class="bi bi-key-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Ganti Password</h5>
                    <p class="text-muted small mb-0">Verifikasi OTP via Telegram</p>
                </div>

                <?= $pesan ?>

                <?php if ($step == 1) { ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small mb-1">NIS / NISN Siswa</label>
                            <input type="text" name="nis_nisn" class="form-control" placeholder="Contoh: 190406" value="<?= isset($_SESSION['reset_nis']) ? $_SESSION['reset_nis'] : '' ?>" required autofocus>
                        </div>
                        <button type="submit" name="kirim_otp" class="btn btn-primary w-100 py-2 fw-bold rounded-pill mb-3 shadow-sm">
                            <i class="bi bi-telegram me-1"></i> Kirim OTP ke Telegram
                        </button>
                    </form>
                <?php } else { ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small mb-1">Kode OTP Telegram</label>
                            <input type="text" name="otp" class="form-control text-center fw-bold fs-3" placeholder="______" maxlength="6" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small mb-1">Password Baru</label>
                            <div class="input-group">
                                <input type="password" name="password_baru" id="passwordBaru" class="form-control" placeholder="Password Baru" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye-slash" id="iconMata"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" name="simpan_password" class="btn btn-success w-100 py-2 fw-bold rounded-pill mb-3 shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> Simpan Password Baru
                        </button>
                    </form>
                <?php } ?>

                <div class="text-center pt-2 border-top">
                    <a href="index.php" class="text-decoration-none small text-muted">
                        <i class="bi bi-arrow-left"></i> Kembali ke Login
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordBaru = document.getElementById('passwordBaru');
    const iconMata = document.getElementById('iconMata');

    if (togglePassword && passwordBaru) {
        togglePassword.addEventListener('click', function () {
            const isPassword = passwordBaru.getAttribute('type') === 'password';
            passwordBaru.setAttribute('type', isPassword ? 'text' : 'password');
            iconMata.classList.toggle('bi-eye');
            iconMata.classList.toggle('bi-eye-slash');
        });
    }
</script>
</body>
</html>