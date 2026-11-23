<?php
session_start();
if (!isset($_SESSION['siswa_logged'])) {
    header("Location: index.php");
    exit;
}
include 'koneksi.php';

$nis = $_SESSION['login_nis'];
$q_siswa = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE nis = '$nis'");
$d_siswa = mysqli_fetch_assoc($q_siswa);

$kelas_siswa = $d_siswa['kelas'];
$jurusan_siswa = $d_siswa['jurusan'];

$q_jadwal = mysqli_query($koneksi, "SELECT * FROM jadwal_pelajaran WHERE kelas = '$kelas_siswa' AND jurusan = '$jurusan_siswa' ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), jam_mulai ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Pelajaran - <?= $d_siswa['nama'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Script HTML2PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
        .kop-teks h3 { margin: 0; font-size: 24px; font-weight: bold; }
        .kop-teks p { margin: 0; font-size: 14px; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="riwayat_siswa.php" class="btn btn-outline-secondary fw-bold"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
        <button onclick="downloadPDF()" class="btn btn-danger fw-bold shadow-sm"><i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF</button>
    </div>

    <!-- AREA CETAK PDF -->
    <div class="card shadow-sm border-0" id="areaCetakPDF">
        <div class="card-body p-4 p-md-5 bg-white">
            
            <!-- KOP SURAT (Otomatis panggil logo.png) -->
            <div class="row align-items-center kop-surat">
                <div class="col-2 text-center">
                    <!-- Pastikan ada gambar logo.png di folder kamu -->
                    <img src="logo.png" alt="Logo" style="width: 100%; max-width: 90px;" onerror="this.style.display='none'">
                </div>
                <div class="col-10 text-center text-md-start kop-teks">
                    <h3>SMK NUR EL-FALAH</h3>
                    <p>Sistem Informasi Akademik & Absensi Digital Terpadu</p>
                    <p>Jadwal Pelajaran Resmi Tahun Ajaran 2026/2027</p>
                </div>
            </div>

            <!-- INFO SISWA -->
            <div class="mb-4">
                <table cellpadding="4" style="font-size: 15px;">
                    <tr><td width="100" class="fw-bold">Nama</td><td>: <?= $d_siswa['nama'] ?></td></tr>
                    <tr><td class="fw-bold">NIS / NISN</td><td>: <?= $nis ?> / <?= $d_siswa['nisn'] ?></td></tr>
                    <tr><td class="fw-bold">Kelas</td><td>: <?= $kelas_siswa ?> <?= $jurusan_siswa ?></td></tr>
                </table>
            </div>

            <!-- TABEL JADWAL -->
            <table class="table table-bordered border-dark align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th width="15%">Hari</th>
                        <th width="20%">Jam</th>
                        <th width="35%">Mata Pelajaran</th>
                        <th width="30%">Guru Pengajar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($q_jadwal) > 0) {
                        while($j = mysqli_fetch_assoc($q_jadwal)) { 
                    ?>
                    <tr>
                        <td class="text-center fw-bold bg-light"><?= $j['hari'] ?></td>
                        <td class="text-center"><?= date('H:i', strtotime($j['jam_mulai'])) ?> - <?= date('H:i', strtotime($j['jam_selesai'])) ?></td>
                        <td class="fw-semibold px-3"><?= $j['mata_pelajaran'] ?></td>
                        <td class="px-3"><?= $j['nama_guru'] ?></td>
                    </tr>
                    <?php } } else { ?>
                    <tr><td colspan="4" class="text-center py-4 fst-italic">Jadwal pelajaran belum diinput oleh Admin.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <div class="text-end mt-4 pt-3">
                <small>Dicetak oleh sistem pada: <?= date('d M Y, H:i') ?></small>
            </div>
        </div>
    </div>
</div>

<script>
    function downloadPDF() {
        const elemen = document.getElementById('areaCetakPDF');
        const opt = {
            margin:       [0.5, 0.5, 0.5, 0.5], // [top, left, bottom, right]
            filename:     'Jadwal_Kelas_<?= $kelas_siswa ?>_<?= $jurusan_siswa ?>.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 3, useCORS: true },
            jsPDF:        { unit: 'in', format: 'A4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(elemen).save();
    }
</script>

</body>
</html>