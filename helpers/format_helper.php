<?php
/**
 * Helper Format Tanggal & Sisa Waktu E-Perpus (Tanpa Denda)
 */

if (!function_exists('format_tanggal')) {
    function format_tanggal($tanggal) {
        if (!$tanggal || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') return '-';
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $timestamp = strtotime($tanggal);
        $split = explode('-', date('Y-m-d', $timestamp));
        $jam = date('H:i', $timestamp);
        return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0] . ' (' . $jam . ' WIB)';
    }
}

if (!function_exists('hitung_sisa_waktu')) {
    function hitung_sisa_waktu($tanggal_jatuh_tempo) {
        $tgl_tempo = strtotime($tanggal_jatuh_tempo);
        $sekarang = time();
        $selisih = $tgl_tempo - $sekarang;

        if ($selisih <= 0) {
            return "Kadaluarsa";
        }

        $hari = floor($selisih / (60 * 60 * 24));
        $jam = floor(($selisih % (60 * 60 * 24)) / (60 * 60));

        if ($hari > 0) {
            return "Sisa " . $hari . " hari " . $jam . " jam";
        } else {
            $menit = floor(($selisih % (60 * 60)) / 60);
            return "Sisa " . $jam . " jam " . $menit . " menit";
        }
    }
}
