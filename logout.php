<?php
session_start();
include 'koneksi.php';

// 1. Hapus token di database jika siswa sedang login
if (isset($_SESSION['siswa_uid'])) {
    $uid_siswa = mysqli_real_escape_string($koneksi, $_SESSION['siswa_uid']);
    mysqli_query($koneksi, "UPDATE data_siswa SET session_token = NULL, token_expires = NULL WHERE uid_rfid = '$uid_siswa'");
}

// 2. HAPUS COOKIE DEVICE TOKEN DARI BROWSER (PENTING!)
if (isset($_COOKIE['siswa_device_token'])) {
    unset($_COOKIE['siswa_device_token']);
    setcookie('siswa_device_token', '', time() - 3600, '/'); 
}

// 3. Hapus seluruh session login
$_SESSION = array();
session_destroy();

// 4. Kembalikan ke halaman login utama
header("Location: index.php");
exit;
?>