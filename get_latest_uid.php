<?php
// Matikan semua error/warning yang bisa merusak output
error_reporting(0); 

// Aktifkan pembersihan memori (menghapus spasi nyasar dari koneksi.php)
ob_start();
include 'koneksi.php';
ob_end_clean(); 

$query = mysqli_query($koneksi, "SELECT uid_rfid FROM device_log WHERE id = 1");
if ($query && mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    // Cetak hanya UID tanpa spasi
    echo trim($row['uid_rfid']);
}
?>