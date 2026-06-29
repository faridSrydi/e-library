<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/format_helper.php';
$current_user = get_user();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Gerbang Literasi - Perpustakaan Digital Berstandar Modern. Jelajahi ribuan koleksi buku dan e-book." />
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — Gerbang Literasi' : 'Gerbang Literasi — Perpustakaan Digital'; ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL; ?>assets/img/icon_head.svg" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="<?= BASE_URL; ?>assets/css/style.css" rel="stylesheet" />
    <?php if (isset($extra_css)) echo $extra_css; ?>
  </head>
  <body style="background: #fff; display: flex; flex-direction: column; min-height: 100vh;">
