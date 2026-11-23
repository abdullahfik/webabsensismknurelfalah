<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// =========================================================================
// OTOMATISASI REKAP ALPHA (Berjalan otomatis setelah jam 08:00 Pagi)
// =========================================================================
$tanggal_hari_ini = date('Y-m-d');
$jam_sekarang     = date('H:i:s');
$jam_batas_alpha  = "08:00:00"; // Batas jam siswa dianggap Alpha jika belum tap kartu

if ($jam_sekarang >= $jam_batas_alpha) {
    // Cari siswa di data_siswa yang BELUM ADA di log_absensi pada hari ini
    $q_generate_alpha = mysqli_query($koneksi, "
        SELECT s.uid_rfid, s.nis 
        FROM data_siswa s
        WHERE NOT EXISTS (
            SELECT 1 FROM log_absensi a 
            WHERE (a.uid_rfid = s.uid_rfid OR a.uid_rfid = s.nis) 
            AND a.tanggal = '$tanggal_hari_ini'
        )
    ");

    if ($q_generate_alpha) {
        while ($siswa = mysqli_fetch_assoc($q_generate_alpha)) {
            $uid_pakai = !empty($siswa['uid_rfid']) ? $siswa['uid_rfid'] : $siswa['nis'];
            if (!empty($uid_pakai)) {
                mysqli_query($koneksi, "
                    INSERT INTO log_absensi (uid_rfid, tanggal, waktu_tap, status, keterangan) 
                    VALUES ('$uid_pakai', '$tanggal_hari_ini', '00:00:00', 'Alpha', 'Tanpa Keterangan')
                ");
            }
        }
    }
}
// =========================================================================

// Ambil data filter dari JavaScript
$filter_kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($koneksi, $_GET['kelas']) : '';
$filter_jurusan = isset($_GET['jurusan']) ? mysqli_real_escape_string($koneksi, $_GET['jurusan']) : '';

$where_conditions = [];

// Gunakan LIKE supaya kalau di database tertulis "11RPL01", filter "11" tetap mendeteksinya
if (!empty($filter_kelas)) {
    $where_conditions[] = "data_siswa.kelas LIKE '%$filter_kelas%'";
}

// Gunakan LIKE untuk mendeteksi singkatan jurusan di kolom kelas (misal "RPL" di dalam "11RPL01") atau di kolom jurusan
if (!empty($filter_jurusan)) {
    $where_conditions[] = "(data_siswa.kelas LIKE '%$filter_jurusan%' OR data_siswa.jurusan LIKE '%$filter_jurusan%')";
}

$where_clause = "";
if (count($where_conditions) > 0) {
    $where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

// Query untuk mengambil data absensi
$query = "SELECT log_absensi.*, data_siswa.nis, data_siswa.nisn, data_siswa.nama, data_siswa.kelas, data_siswa.jurusan 
          FROM log_absensi 
          LEFT JOIN data_siswa ON log_absensi.uid_rfid = data_siswa.uid_rfid 
          $where_clause
          ORDER BY log_absensi.tanggal DESC, log_absensi.waktu_tap DESC";

$result = mysqli_query($koneksi, $query);
$no = 1;

if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) { 
        $bg_color = 'bg-secondary';
        if($row['status'] == 'Hadir') $bg_color = 'bg-success';
        else if($row['status'] == 'Terlambat') $bg_color = 'bg-danger';
        else if($row['status'] == 'Sakit') $bg_color = 'bg-warning text-dark';
        else if($row['status'] == 'Izin') $bg_color = 'bg-info text-dark';
        else if($row['status'] == 'Alpha') $bg_color = 'bg-dark'; // Warna badge khusus untuk Alpha
?>
<tr>
    <td class="text-center"><?= $no++ ?></td>
    <td class="text-center"><?= $row['tanggal'] ?></td>
    <td class="text-center fw-bold text-primary"><?= ($row['waktu_tap'] == '00:00:00') ? '-' : $row['waktu_tap'] ?></td>
    <td>
        <div class="fw-bold"><?= !empty($row['nisn']) ? $row['nisn'] : '-' ?></div>
        <small class="text-muted">NIS: <?= !empty($row['nis']) ? $row['nis'] : '-' ?></small>
    </td>
    <td class="fw-semibold">
        <?= !empty($row['nama']) ? $row['nama'] : '<span class="text-danger fst-italic">Siswa Tidak Dikenal</span>' ?>
    </td>
    <td><?= !empty($row['kelas']) ? $row['kelas'].' '.$row['jurusan'] : '-' ?></td>
    <td class="text-center">
        <span class="badge <?= $bg_color ?> rounded-pill px-3 shadow-sm"><?= $row['status'] ?></span>
    </td>
</tr>
<?php 
    } 
} else {
    echo '<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i> Belum ada data absensi untuk kelas/jurusan ini</td></tr>';
}
?>