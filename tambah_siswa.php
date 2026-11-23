<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header("Location: index.php");
    exit;
}
include 'koneksi.php';

$pesan = '';
if (isset($_POST['simpan'])) {
    $nis = mysqli_real_escape_string($koneksi, $_POST['nis']);
    $nisn = mysqli_real_escape_string($koneksi, $_POST['nisn']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $jenis_kelamin = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $no_telp = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan']);
    $uid_rfid = mysqli_real_escape_string($koneksi, $_POST['uid_rfid']);

    $query = "INSERT INTO data_siswa (nis, nisn, nama, jenis_kelamin, no_telp, kelas, jurusan, uid_rfid) 
              VALUES ('$nis', '$nisn', '$nama', '$jenis_kelamin', '$no_telp', '$kelas', '$jurusan', '$uid_rfid')";
    
    if (mysqli_query($koneksi, $query)) {
        $pesan = "<div class='alert alert-success alert-dismissible fade show'>
                    <i class='bi bi-check-circle-fill me-2'></i>Data siswa berhasil ditambahkan!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
                  
        mysqli_query($koneksi, "UPDATE device_log SET uid_rfid = '' WHERE id = 1");
    } else {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show'>
                    <i class='bi bi-exclamation-triangle-fill me-2'></i>Gagal: " . mysqli_error($koneksi) . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Siswa - SMK Nur El-Falah</title>
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
        <div class="col-md-6">
            <div class="card shadow-sm border-0 p-4">
                <h4 class="fw-bold text-primary mb-3"><i class="bi bi-person-plus-fill me-2"></i> Tambah Data Siswa</h4>
                
                <?= $pesan ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold small">NIS</label>
                            <input type="text" name="nis" class="form-control" required placeholder="Masukkan NIS">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold small">NISN</label>
                            <input type="text" name="nisn" class="form-control" required placeholder="Masukkan NISN">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Masukkan Nama Lengkap">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold small">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold small"><i class="bi bi-telephone-fill text-success me-1"></i> Nomor HP / WhatsApp</label>
                            <input type="text" name="no_telp" class="form-control" placeholder="Contoh: 081234567890" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-semibold small">Tingkat Kelas</label>
                            <select name="kelas" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <option value="X">X (Kelas 10)</option>
                                <option value="XI">XI (Kelas 11)</option>
                                <option value="XII">XII (Kelas 12)</option>
                            </select>
                        </div>
                        <div class="col-md-7 mb-3">
                            <label class="form-label fw-semibold small">Jurusan & Rombel</label>
                            <select name="jurusan" class="form-select" required>
                                <option value="">-- Pilih Jurusan / Rombel --</option>
                                <optgroup label="TKJ">
                                    <option value="TKJ 01">TKJ 01</option>
                                    <option value="TKJ 02">TKJ 02</option>
                                    <option value="TKJ 03">TKJ 03</option>
                                </optgroup>
                                <optgroup label="RPL">
                                    <option value="RPL 01">RPL 01</option>
                                    <option value="RPL 02">RPL 02</option>
                                </optgroup>
                                <optgroup label="TKR">
                                    <option value="TKR 01">TKR 01</option>
                                    <option value="TKR 02">TKR 02</option>
                                </optgroup>
                                <optgroup label="TSM">
                                    <option value="TSM 01">TSM 01</option>
                                    <option value="TSM 02">TSM 02</option>
                                </optgroup>
                                <optgroup label="OTKP">
                                    <option value="OTKP 01">OTKP 01</option>
                                    <option value="OTKP 02">OTKP 02</option>
                                </optgroup>
                                <optgroup label="AKL">
                                    <option value="AKL 01">AKL 01</option>
                                    <option value="AKL 02">AKL 02</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">UID Kartu RFID</label>
                        <div class="input-group">
                            <input type="text" id="uid_rfid_input" name="uid_rfid" class="form-control text-primary fw-bold" placeholder="Tap kartu di alat untuk ambil UID" required readonly>
                            <button type="button" class="btn btn-warning" onclick="document.getElementById('uid_rfid_input').value = '';"><i class="bi bi-eraser"></i></button>
                        </div>
                    </div>

                    <button type="submit" name="simpan" class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-save me-1"></i> Simpan Data Siswa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    setInterval(function() {
        fetch('get_latest_uid.php?t=' + Date.now())
            .then(response => response.text())
            .then(data => {
                let uid = data.toUpperCase().replace(/[^A-Z0-9]/g, '');
                if(uid.length >= 4) {
                    document.getElementById('uid_rfid_input').value = uid;
                }
            })
            .catch(error => console.error('Error:', error));
    }, 1000); 
</script>
</body>
</html>