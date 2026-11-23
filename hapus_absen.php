<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

if (isset($_GET['id'])) {
    $id_absen = $_GET['id'];

    // Hapus data log absensi berdasarkan id_absen
    $query = "DELETE FROM log_absensi WHERE id_absen = '$id_absen'";
    
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Status absen berhasil direset! Siswa bisa melakukan tap kartu ulang.'); window.location.href='index.php';</script>";
    } else {
        echo "Gagal menghapus data: " . mysqli_error($koneksi);
    }
} else {
    header("Location: index.php");
}
?>