<?php
session_start();
if (!isset($_SESSION['admin_logged']) && !isset($_SESSION['guru_logged'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uid_rfid = mysqli_real_escape_string($koneksi, $_POST['uid_rfid']);
    $nis = mysqli_real_escape_string($koneksi, $_POST['nis']);
    $nisn = mysqli_real_escape_string($koneksi, $_POST['nisn']); // Tambahan NISN
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    
    // Menggabungkan tingkat dan sub kelas (Contoh: "12" + "TKJ4" = "12TKJ4")
    $tingkat = mysqli_real_escape_string($koneksi, $_POST['tingkat']);
    $sub_kelas = mysqli_real_escape_string($koneksi, $_POST['sub_kelas']);
    $kelas_gabungan = $tingkat . strtoupper(str_replace(' ', '', $sub_kelas)); 
    
    $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan']);
    $jenis_kelamin = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);

    // Cek apakah UID sudah terdaftar sebelumnya
    $cek_uid = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE uid_rfid = '$uid_rfid'");
    if (mysqli_num_rows($cek_uid) > 0 && !empty($uid_rfid)) {
        echo "<script>alert('Gagal! Kartu RFID ini sudah terdaftar atas nama siswa lain.'); window.location.href='tambah_siswa.php';</script>";
        exit;
    }

    // Simpan ke database (Sudah Termasuk NISN)
    $query = "INSERT INTO data_siswa (uid_rfid, nis, nisn, nama, kelas, jurusan, jenis_kelamin) 
              VALUES ('$uid_rfid', '$nis', '$nisn', '$nama', '$kelas_gabungan', '$jurusan', '$jenis_kelamin')";
    
    if (mysqli_query($koneksi, $query)) {
        // Hapus history tap di alat agar siap untuk daftar kartu selanjutnya
        mysqli_query($koneksi, "UPDATE device_log SET uid_rfid = '' WHERE id = 1");
        
        echo "<script>alert('Data siswa berhasil disimpan!'); window.location.href='tambah_siswa.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data: " . mysqli_error($koneksi) . "'); window.location.href='tambah_siswa.php';</script>";
    }
}
?>