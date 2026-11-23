<?php
date_default_timezone_set('Asia/Jakarta');
include 'koneksi.php';

$content = file_get_contents("php://input");
$update  = json_decode($content, true);

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text    = trim($update['message']['text']);

    if (strpos($text, '/start') === 0) {
        $parts = explode(' ', $text);
        
        if (isset($parts[1]) && !empty($parts[1])) {
            $nis_siswa = mysqli_real_escape_string($koneksi, trim($parts[1]));

            $cek = mysqli_query($koneksi, "SELECT * FROM data_siswa WHERE nis = '$nis_siswa' OR nisn = '$nis_siswa'");
            
            if ($cek && mysqli_num_rows($cek) > 0) {
                $siswa = mysqli_fetch_assoc($cek);
                $uid_siswa = $siswa['uid_rfid'];

                // HANYA UPDATE TELEGRAM ID (TIDAK ADA GENERATE OTP DI SINI)
                mysqli_query($koneksi, "UPDATE data_siswa SET telegram_id = '$chat_id' WHERE uid_rfid = '$uid_siswa'");

                $balasan = "HALO *" . strtoupper($siswa['nama']) . "* 👋\n\n";
                $balasan .= "✅ *Akun Telegram kamu berhasil terhubung!*\n\n";
                $balasan .= "Silakan kembali ke web absensi untuk melanjutkan login.";

                sendTelegramMessage($chat_id, $balasan);
                exit;
            } else {
                sendTelegramMessage($chat_id, "⚠️ NIS ($nis_siswa) tidak ditemukan di database sekolah.");
                exit;
            }
        }
    }

    sendTelegramMessage($chat_id, "Silakan minta kode OTP melalui portal absensi web sekolah.");
}

function sendTelegramMessage($chat_id, $text) {
    $bot_token = "8983955338:AAGpnyJSXwTjzP-uPpE0VjW_0prlSvPHwfk"; 
    $url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($text) . "&parse_mode=Markdown";
    @file_get_contents($url);
}
?>