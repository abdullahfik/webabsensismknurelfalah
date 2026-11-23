<?php
ob_start();
session_start();
date_default_timezone_set('Asia/Jakarta');

include 'koneksi.php';

// Cek Session Login Aktif
if (isset($_SESSION['admin_logged']) || isset($_SESSION['guru_logged'])) {
    header("Location: dashboard.php");
    exit;
}
if (isset($_SESSION['siswa_logged']) && $_SESSION['siswa_logged'] === true) {
    header("Location: riwayat_siswa.php");
    exit;
}

// Auto-Login via Cookie Device Token (14 Hari)
if (isset($_COOKIE['siswa_device_token']) && !empty($_COOKIE['siswa_device_token'])) {
    $token = mysqli_real_escape_string($koneksi, $_COOKIE['siswa_device_token']);
    $waktu_sekarang = date('Y-m-d H:i:s');

    $cek_token = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE session_token = '$token' AND token_expires >= '$waktu_sekarang'");

    if ($cek_token && mysqli_num_rows($cek_token) > 0) {
        $siswa = mysqli_fetch_assoc($cek_token);

        $_SESSION['siswa_logged'] = true;
        $_SESSION['siswa_nis']    = $siswa['nis'];
        $_SESSION['siswa_nama']   = $siswa['nama'];

        header("Location: riwayat_siswa.php");
        exit;
    }
}

$error = '';
$token_bot = "8983955338:AAGpnyJSXwTjzP-uPpE0VjW_0prlSvPHwfk";
$step_siswa = isset($_SESSION['step_login_siswa']) ? $_SESSION['step_login_siswa'] : 1;

// Logic Login Guru / Admin
if (isset($_POST['login_admin'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $query_admin = mysqli_query($koneksi, "SELECT * FROM admin WHERE username = '$username' AND password = '$password'");
    if ($query_admin && mysqli_num_rows($query_admin) > 0) {
        $_SESSION['admin_logged'] = true;
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit;
    } 
    
    $query_guru = mysqli_query($koneksi, "SELECT * FROM guru WHERE username = '$username' AND password = '$password'");
    if ($query_guru && mysqli_num_rows($query_guru) > 0) {
        $_SESSION['guru_logged'] = true;
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit;
    }
    $error = "Username atau Password Admin/Guru salah!";
}

// Logic Login Siswa
if (isset($_POST['login_siswa'])) {
    $nis_nisn = mysqli_real_escape_string($koneksi, trim($_POST['nis_nisn']));
    $password = mysqli_real_escape_string($koneksi, trim($_POST['password']));
    $waktu_sekarang = date('Y-m-d H:i:s');

    $query = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE (nis = '$nis_nisn' OR nisn = '$nis_nisn') AND password = '$password'");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $siswa = mysqli_fetch_assoc($query);

        if (empty($siswa['telegram_id'])) {
            $error = "Aplikasi Telegram kamu belum terhubung! Silakan klik 'Hubungkan Telegram Baru' di bawah.";
            $step_siswa = 1;
        } 
        elseif ($siswa['password'] == '123456') {
            $_SESSION['reset_nis']  = $siswa['nis'];
            $_SESSION['reset_nama'] = $siswa['nama'];
            header("Location: ganti_password.php?first=1");
            exit;
        } 
        elseif (empty($siswa['session_token']) || $siswa['token_expires'] < $waktu_sekarang) {
            $chat_id_siswa = $siswa['telegram_id'];
            $otp = rand(100000, 999999);
            $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $_SESSION['otp_code']   = $otp;
            $_SESSION['login_nis']  = $siswa['nis'];
            $_SESSION['nama_siswa'] = $siswa['nama'];

            $nis_siswa = $siswa['nis'];
            mysqli_query($koneksi, "UPDATE data_siswa SET otp_code = '$otp', otp_expires = '$otp_expires' WHERE nis = '$nis_siswa'");

            $pesan = "🔐 *KODE VERIFIKASI LOGIN SISWA*\n\n";
            $pesan .= "Halo, *" . $siswa['nama'] . "* 👋\n";
            $pesan .= "Kode verifikasi kamu: *$otp*\n\n";
            $pesan .= "_Verifikasi sesi kamu kosong/kadaluarsa. Kode berlaku 15 menit._";

            $url = "https://api.telegram.org/bot$token_bot/sendMessage?chat_id=$chat_id_siswa&text=" . urlencode($pesan) . "&parse_mode=Markdown";
            @file_get_contents($url);

            $_SESSION['step_login_siswa'] = 2;
            $step_siswa = 2;
        }
        else {
            $_SESSION['siswa_logged'] = true;
            $_SESSION['siswa_nis']    = $siswa['nis'];
            $_SESSION['siswa_nama']   = $siswa['nama'];

            setcookie('siswa_device_token', $siswa['session_token'], strtotime($siswa['token_expires']), "/", "", false, true);

            header("Location: riwayat_siswa.php");
            exit;
        }
    } else {
        $error = "NIS / NISN atau Password yang Anda masukkan salah!";
        $step_siswa = 1;
    }
} 
elseif (isset($_POST['verifikasi_otp_siswa'])) {
    $user_otp = trim($_POST['otp']);
    $waktu_sekarang = date('Y-m-d H:i:s');
    $nis_siswa = isset($_SESSION['login_nis']) ? $_SESSION['login_nis'] : '';

    if (!empty($nis_siswa)) {
        $cek_otp = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE nis = '$nis_siswa' AND otp_code = '$user_otp' AND otp_expires >= '$waktu_sekarang'");

        if ($cek_otp && mysqli_num_rows($cek_otp) > 0) {
            $siswa = mysqli_fetch_assoc($cek_otp);

            $_SESSION['siswa_logged'] = true;
            $_SESSION['siswa_nis']    = $siswa['nis'];
            $_SESSION['siswa_nama']   = $siswa['nama'];

            $token_device  = bin2hex(random_bytes(32));
            $token_expires = date('Y-m-d H:i:s', strtotime('+14 days'));

            mysqli_query($koneksi, "UPDATE data_siswa SET session_token = '$token_device', token_expires = '$token_expires', otp_code = NULL, otp_expires = NULL WHERE nis = '$nis_siswa'");

            setcookie('siswa_device_token', $token_device, time() + (14 * 86400), "/", "", false, true);

            unset($_SESSION['step_login_siswa']); 
            unset($_SESSION['otp_code']);
            unset($_SESSION['login_nis']);
            unset($_SESSION['nama_siswa']);
            
            header("Location: riwayat_siswa.php");
            exit;
        } else {
            $error = "Kode OTP salah atau sudah kadaluarsa!";
            $step_siswa = 2;
        }
    } else {
        $error = "Sesi verifikasi telah habis, silakan masukkan NIS dan Password kembali.";
        $step_siswa = 1;
    }
} 
elseif (isset($_POST['reset_siswa'])) {
    unset($_SESSION['step_login_siswa']);
    unset($_SESSION['otp_code']);
    unset($_SESSION['login_nis']);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - SMK Nur El-Falah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { 
            background: linear-gradient(135deg, #0d6efd 0%, #198754 100%); 
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-login { 
            backdrop-filter: blur(10px); 
            background: rgba(255, 255, 255, 0.95); 
            border-radius: 1.25rem; 
        }
        .nav-pills .nav-link {
            color: #6c757d;
            font-weight: 600;
            border-radius: 0.75rem;
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
            transition: all 0.3s ease-in-out;
        }
        .nav-pills .nav-link.active {
            background-color: #198754;
            color: white;
            box-shadow: 0 4px 10px rgba(25, 135, 84, 0.3);
        }
        .form-control {
            padding: 0.65rem 0.9rem;
            font-size: 0.95rem;
        }
        .input-group-text {
            padding: 0.65rem 0.9rem;
        }
        #loading-screen {
            position: fixed;
            width: 100%;
            height: 100vh;
            background: #ffffff;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.4s ease;
        }
        .spinner-ring {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(25, 135, 84, 0.2); 
            border-top-color: #198754;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        @media (max-width: 576px) {
            .container-mobile {
                padding-left: 15px;
                padding-right: 15px;
            }
            .card-login {
                padding: 1.25rem !important;
            }
            .brand-title {
                font-size: 1.35rem;
            }
        }
    </style>
</head>
<body>

<div id="loading-screen">
    <div class="spinner-ring mb-3"></div>
    <h6 class="text-success fw-bold">Memuat Portal...</h6>
</div>

<div class="container container-mobile py-4 my-auto">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-9 col-md-7 col-lg-5 col-xl-4">
            <div class="card card-login shadow-lg border-0 p-3 p-sm-4">
                <div class="card-body p-1 p-sm-2">
                    
                    <div class="text-center mb-3">
                        <img src="logo.png" alt="Logo SMK" style="width: 70px; height: auto;" class="mb-2">
                        <h4 class="fw-bold text-dark mb-1 brand-title">SMK Nur El-Falah</h4>
                        <p class="text-muted small mb-0">Portal Absensi Digital</p>
                    </div>

                    <?php if (!empty($error)) { ?>
                        <div class="alert alert-danger alert-dismissible fade show text-center small py-2 px-3 mb-3" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i><?= $error ?>
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                        </div>
                    <?php } ?>

                    <!-- TAB MENU -->
                    <ul class="nav nav-pills nav-justified mb-3 gap-2" id="loginTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link w-100 <?= !isset($_POST['login_admin']) ? 'active' : '' ?>" id="siswa-tab" data-bs-toggle="tab" data-bs-target="#siswa" type="button"><i class="bi bi-person-badge me-1"></i> Siswa</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link w-100 <?= isset($_POST['login_admin']) ? 'active' : '' ?>" id="guru-tab" data-bs-toggle="tab" data-bs-target="#guru" type="button"><i class="bi bi-person-workspace me-1"></i> Guru/Admin</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="loginTabsContent">
                        <!-- TAB SISWA -->
                        <div class="tab-pane fade <?= !isset($_POST['login_admin']) ? 'show active' : '' ?>" id="siswa">
                            <?php if ($step_siswa == 1) { ?>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-secondary small mb-1">NIS / NISN Siswa</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-card-text text-muted"></i></span>
                                            <input type="text" name="nis_nisn" class="form-control border-start-0 ps-0" placeholder="Contoh: 190406" required autofocus>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-semibold text-secondary small mb-1">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                            <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="Password" required>
                                        </div>
                                    </div>

                                    <div class="text-end mb-3">
                                        <a href="ganti_password.php" class="text-decoration-none small text-danger fw-semibold">Lupa Password?</a>
                                    </div>

                                    <button type="submit" name="login_siswa" class="btn btn-success w-100 py-2 fw-bold shadow-sm rounded-pill">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Siswa
                                    </button>
                                </form>
                            <?php } else { ?>
                                <div class="alert alert-warning text-center small py-2 mb-3">
                                    Kode OTP dikirim ke Telegram <b><?= isset($_SESSION['nama_siswa']) ? $_SESSION['nama_siswa'] : '' ?></b>.
                                </div>
                                <form method="POST">
                                    <div class="mb-3 text-center">
                                        <input type="text" name="otp" class="form-control text-center fw-bold fs-3 tracking-widest" placeholder="______" maxlength="6" required autofocus>
                                    </div>
                                    <button type="submit" name="verifikasi_otp_siswa" class="btn btn-success w-100 py-2 fw-bold shadow-sm rounded-pill mb-2">
                                        Verifikasi & Masuk
                                    </button>
                                    <button type="submit" name="reset_siswa" class="btn btn-outline-secondary w-100 py-1 btn-sm rounded-pill">
                                        Batal
                                    </button>
                                </form>
                            <?php } ?>
                            
                            <div class="text-center mt-3 pt-2 border-top">
                                <a href="konek_telegram.php" class="text-decoration-none small text-primary fw-semibold">
                                    <i class="bi bi-link-45deg"></i> Hubungkan Telegram Baru
                                </a>
                            </div>
                        </div>

                        <!-- TAB GURU / ADMIN -->
                        <div class="tab-pane fade <?= isset($_POST['login_admin']) ? 'show active' : '' ?>" id="guru">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-secondary small mb-1">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                        <input type="text" name="username" class="form-control border-start-0 ps-0" placeholder="Username" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold text-secondary small mb-1">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                        <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="Password" required>
                                    </div>
                                </div>
                                <button type="submit" name="login_admin" class="btn btn-primary w-100 py-2 fw-bold shadow-sm rounded-pill">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Admin
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.addEventListener('load', function() {
        const loader = document.getElementById('loading-screen');
        loader.style.opacity = '0';
        setTimeout(() => { loader.style.display = 'none'; }, 400);
    });
</script>
</body>
</html>