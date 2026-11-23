<?php
session_start();
include 'koneksi.php';

$pesan = '';
$username_bot = "SMKNURELFALAH_bot"; 

if (isset($_POST['hubungkan'])) {
    $nis_input = mysqli_real_escape_string($koneksi, trim($_POST['nis_nisn']));

    $query = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE nis = '$nis_input' OR nisn = '$nis_input' OR no_telp = '$nis_input'");

    if ($query && mysqli_num_rows($query) > 0) {
        $siswa = mysqli_fetch_assoc($query);
        
        $link_telegram = "https://t.me/" . $username_bot . "?start=" . $siswa['nis'];
        header("Location: " . $link_telegram);
        exit;
    } else {
        $pesan = "<div class='alert alert-danger small text-center py-2 mb-3'>NIS / NISN / No. HP tidak ditemukan!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Hubungkan Telegram - SMK Nur El-Falah</title>
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
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 55px; height: 55px;">
                        <i class="bi bi-telegram fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Konek Telegram</h5>
                    <p class="text-muted small mb-0">Masukkan NIS untuk menghubungkan akun Telegram secara otomatis.</p>
                </div>

                <?= $pesan ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small mb-1">NIS / NISN / No. HP</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-vcard text-muted"></i></span>
                            <input type="text" name="nis_nisn" class="form-control border-start-0 ps-0" placeholder="Contoh: 190406" required autofocus>
                        </div>
                    </div>
                    <button type="submit" name="hubungkan" class="btn btn-success w-100 py-2 fw-bold rounded-pill mb-3 shadow-sm">
                        <i class="bi bi-link-45deg me-1"></i> Hubungkan Otomatis
                    </button>
                </form>

                <div class="text-center pt-2 border-top">
                    <a href="index.php" class="text-decoration-none small text-muted">
                        <i class="bi bi-arrow-left"></i> Kembali ke Login Siswa
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>