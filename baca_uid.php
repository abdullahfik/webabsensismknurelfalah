<?php
include 'koneksi.php';

// Ambil data RFID terakhir yang masuk ke tabel tmprfid
$query = mysqli_query($koneksi, "SELECT * FROM tmprfid");
if (mysqli_num_rows($query) > 0) {
    $data = mysqli_fetch_assoc($query);
    echo $data['rfid'];
}
?>