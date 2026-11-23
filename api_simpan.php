<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (isset($_POST['uid'])) {
    $uid = mysqli_real_escape_string($koneksi, $_POST['uid']);
    $tanggal = date('Y-m-d');
    
    // 1. WAJIB DI PALING ATAS: Simpan UID ke device_log agar Tambah Siswa bisa kapan saja!
    mysqli_query($koneksi, "INSERT INTO device_log (id, uid_rfid) VALUES (1, '$uid') ON DUPLICATE KEY UPDATE uid_rfid = '$uid'");
    
    // 2. Cek Pengaturan Simulasi Jam
    $cek_simulasi = mysqli_query($koneksi, "SELECT * FROM pengaturan_simulasi WHERE id = 1");
    if ($cek_simulasi && mysqli_num_rows($cek_simulasi) > 0) {
        $data_sim = mysqli_fetch_assoc($cek_simulasi);
        if (isset($data_sim['status_simulasi']) && $data_sim['status_simulasi'] == 'aktif') {
            $waktu = $data_sim['jam_simulasi'];
        } else {
            $waktu = date('H:i:s');
        }
    } else {
        $waktu = date('H:i:s'); 
    }
    
    // 3. Aturan Blokir Absensi di Luar Jam Sekolah (Hanya memblokir absensi, device_log sudah tersimpan di atas)
    if (empty($data_sim['status_simulasi']) || $data_sim['status_simulasi'] != 'aktif') {
        if ($waktu >= "18:00:00" || $waktu < "05:00:00") {
            echo "Diluar Jam|Belum Buka";
            exit;
        }
    }
    
    // 4. Cek Data Siswa
    $cek_siswa = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE uid_rfid = '$uid'");
    
    if ($cek_siswa && mysqli_num_rows($cek_siswa) > 0) {
        $data_s = mysqli_fetch_assoc($cek_siswa);
        $nama_siswa = $data_s['nama'];
        
        $cek_absen = mysqli_query($koneksi, "SELECT * FROM log_absensi WHERE uid_rfid = '$uid' AND tanggal = '$tanggal'");
        
        if ($cek_absen && mysqli_num_rows($cek_absen) > 0) {
            echo "Sdh Absen|" . $nama_siswa; 
        } else {
            $batas_tepat_waktu = "07:15:59";
            
            if ($waktu <= $batas_tepat_waktu) {
                $status = "Hadir";
                $keterangan = "Tepat Waktu (<= 07:15)";
            } else {
                $status = "Terlambat";
                $keterangan = "Datang lewat dari 07:15";
            }
            
            $query_simpan = "INSERT INTO log_absensi (uid_rfid, tanggal, waktu_tap, status, keterangan) 
                             VALUES ('$uid', '$tanggal', '$waktu', '$status', '$keterangan')";
                             
            if (mysqli_query($koneksi, $query_simpan)) {
                echo $status . "|" . $nama_siswa; 
            } else {
                echo "Gagal Sistem|Error DB";
            }
        }
    } else {
        echo "Tdk Terdaftar|Tidak Dikenal"; 
    }
} else {
    echo "Akses Ditolak|Error";
}
?>