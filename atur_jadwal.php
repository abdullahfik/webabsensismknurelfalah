<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header("Location: index.php");
    exit;
}
include 'koneksi.php';

// Hapus Jadwal
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM jadwal_pelajaran WHERE id = '$id'");
    echo "<script>alert('Jadwal dihapus!'); window.location='atur_jadwal.php';</script>";
}

// Simpan Jadwal Baru
if (isset($_POST['simpan'])) {
    $kelas = $_POST['kelas'];
    $jurusan = $_POST['jurusan'];
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $mapel = $_POST['mata_pelajaran'];
    $guru = $_POST['nama_guru'];

    mysqli_query($koneksi, "INSERT INTO jadwal_pelajaran (kelas, jurusan, hari, jam_mulai, jam_selesai, mata_pelajaran, nama_guru) VALUES ('$kelas', '$jurusan', '$hari', '$jam_mulai', '$jam_selesai', '$mapel', '$guru')");
    echo "<script>alert('Jadwal berhasil ditambahkan!'); window.location='atur_jadwal.php';</script>";
}

$q_kelas = mysqli_query($koneksi, "SELECT DISTINCT kelas FROM data_siswa WHERE kelas != '' ORDER BY kelas ASC");
$q_jurusan = mysqli_query($koneksi, "SELECT DISTINCT jurusan FROM data_siswa WHERE jurusan != '' ORDER BY jurusan ASC");
$q_jadwal = mysqli_query($koneksi, "SELECT * FROM jadwal_pelajaran ORDER BY kelas ASC, jurusan ASC, FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'), jam_mulai ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Atur Jadwal - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-arrow-left me-2"></i> Kembali ke Dashboard</a>
    </div>
</nav>

<div class="container mb-5">
    <!-- Form Input Jadwal -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold text-primary"><i class="bi bi-calendar-plus me-2"></i> Tambah Jadwal Baru</h5></div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select name="kelas" class="form-select" required>
                        <option value="">Pilih</option>
                        <option value="10">10</option><option value="11">11</option><option value="12">12</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Jurusan</label>
                    <select name="jurusan" class="form-select" required>
                        <option value="">Pilih</option>
                        <?php while($j = mysqli_fetch_assoc($q_jurusan)) { echo "<option>{$j['jurusan']}</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Hari</label>
                    <select name="hari" class="form-select" required>
                        <option>Senin</option><option>Selasa</option><option>Rabu</option><option>Kamis</option><option>Jumat</option><option>Sabtu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Jam Mulai</label>
                    <input type="time" name="jam_mulai" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Jam Selesai</label>
                    <input type="time" name="jam_selesai" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Mata Pelajaran</label>
                    <input type="text" name="mata_pelajaran" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Nama Guru Pengajar</label>
                    <input type="text" name="nama_guru" class="form-control" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold"><i class="bi bi-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Daftar Jadwal -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-task me-2"></i> Daftar Jadwal Tersimpan</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr><th>Kelas</th><th>Hari</th><th>Jam</th><th>Mata Pelajaran</th><th>Guru</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($q_jadwal)) { ?>
                        <tr>
                            <td class="fw-bold"><?= $row['kelas'] ?> <?= $row['jurusan'] ?></td>
                            <td><?= $row['hari'] ?></td>
                            <td><?= date('H:i', strtotime($row['jam_mulai'])) ?> - <?= date('H:i', strtotime($row['jam_selesai'])) ?></td>
                            <td><?= $row['mata_pelajaran'] ?></td>
                            <td><?= $row['nama_guru'] ?></td>
                            <td><a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus jadwal ini?')"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>