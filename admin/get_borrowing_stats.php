<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

// Pastikan user sudah login sebagai admin atau petugas
require_role(['admin', 'petugas']);

header('Content-Type: application/json');

$filter = $_GET['filter'] ?? 'month'; // 'today', 'week', 'month', 'year'
$db = Database::getConnection();

$labels = [];
$totals = [];

switch ($filter) {
    case 'today':
        // Tampilkan statistik per jam untuk hari ini (24 jam)
        for ($i = 0; $i < 24; $i++) {
            $hour = sprintf("%02d:00", $i);
            $labels[] = $hour;
            $totals[$i] = 0;
        }
        
        $stmt = $db->prepare("
            SELECT HOUR(tanggal_pinjam) as jam, COUNT(*) as total
            FROM borrowings
            WHERE tanggal_pinjam IS NOT NULL 
              AND DATE(tanggal_pinjam) = CURDATE()
            GROUP BY jam
        ");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            $totals[(int)$row['jam']] = (int)$row['total'];
        }
        $totals = array_values($totals);
        break;

    case 'week':
        // Tampilkan statistik per hari untuk 7 hari terakhir
        $indo_days = [
            'Sun' => 'Min', 'Mon' => 'Sen', 'Tue' => 'Sel', 
            'Wed' => 'Rab', 'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab'
        ];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-$i days");
            $day_name = $date->format('D');
            $labels[] = $indo_days[$day_name] ?? $day_name;
            $totals[$date->format('Y-m-d')] = 0;
        }
        
        $stmt = $db->prepare("
            SELECT DATE(tanggal_pinjam) as tanggal, COUNT(*) as total
            FROM borrowings
            WHERE tanggal_pinjam IS NOT NULL 
              AND tanggal_pinjam >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY tanggal
        ");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            if (isset($totals[$row['tanggal']])) {
                $totals[$row['tanggal']] = (int)$row['total'];
            }
        }
        $totals = array_values($totals);
        break;

    case '1month':
        // Tampilkan statistik per hari untuk 30 hari terakhir
        for ($i = 29; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-$i days");
            $day_label = (int)$date->format('j') . '/' . (int)$date->format('n');
            $labels[] = $day_label;
            $totals[$date->format('Y-m-d')] = 0;
        }
        
        $stmt = $db->prepare("
            SELECT DATE(tanggal_pinjam) as tanggal, COUNT(*) as total
            FROM borrowings
            WHERE tanggal_pinjam IS NOT NULL 
              AND tanggal_pinjam >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
            GROUP BY tanggal
        ");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            if (isset($totals[$row['tanggal']])) {
                $totals[$row['tanggal']] = (int)$row['total'];
            }
        }
        $totals = array_values($totals);
        break;

    case 'year':
        // Tampilkan statistik per bulan untuk 12 bulan terakhir
        $indo_months = [
            'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
            'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agu',
            'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
        ];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-$i months");
            $eng_month = $date->format('M');
            $label = $indo_months[$eng_month] ?? $eng_month;
            $labels[] = $label;
            $totals[$date->format('Y-m')] = 0;
        }
        
        $stmt = $db->prepare("
            SELECT DATE_FORMAT(tanggal_pinjam, '%Y-%m') as y_m, COUNT(*) as total
            FROM borrowings
            WHERE tanggal_pinjam IS NOT NULL 
              AND tanggal_pinjam >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
            GROUP BY y_m
        ");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            if (isset($totals[$row['y_m']])) {
                $totals[$row['y_m']] = (int)$row['total'];
            }
        }
        $totals = array_values($totals);
        break;

    case 'month':
    default:
        // Tampilkan statistik per bulan untuk 6 bulan terakhir
        $indo_months = [
            'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
            'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agu',
            'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
        ];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-$i months");
            $eng_month = $date->format('M');
            $label = $indo_months[$eng_month] ?? $eng_month;
            $labels[] = $label;
            $totals[$date->format('Y-m')] = 0;
        }
        
        $stmt = $db->prepare("
            SELECT DATE_FORMAT(tanggal_pinjam, '%Y-%m') as y_m, COUNT(*) as total
            FROM borrowings
            WHERE tanggal_pinjam IS NOT NULL 
              AND tanggal_pinjam >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY y_m
        ");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            if (isset($totals[$row['y_m']])) {
                $totals[$row['y_m']] = (int)$row['total'];
            }
        }
        $totals = array_values($totals);
        break;
}

echo json_encode([
    'labels' => $labels,
    'totals' => $totals
]);
