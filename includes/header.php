<?php
/** @var string $judul  @var string $menu */
$menuItems = [
    'dashboard' => ['Dashboard', 'dashboard.php',
        '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>'],
    'transaksi' => ['Pemasukan & Pengeluaran', 'transaksi.php',
        '<path d="M7 4v16M3 16l4 4 4-4M17 20V4M13 8l4-4 4 4"/>'],
    'laporan'   => ['Laporan Bulanan', 'laporan.php',
        '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>'],
    'akun'      => ['Akun Admin', 'akun.php',
        '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
];
$flash = ambil_flash();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> · Keuangan PAC IPNU IPPNU Cikarang Utara</title>
<link rel="icon" href="assets/img/logo.jpg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <a class="brand" href="dashboard.php">
      <img src="assets/img/logo.jpg" alt="Logo PAC IPNU IPPNU Cikarang Utara">
      <span>
        <strong>PAC IPNU IPPNU</strong>
        <small>Cikarang Utara</small>
      </span>
    </a>
    <nav aria-label="Menu utama">
      <?php foreach ($menuItems as $key => [$label, $href, $icon]): ?>
        <a href="<?= $href ?>" class="nav-link<?= $menu === $key ? ' aktif' : '' ?>"<?= $menu === $key ? ' aria-current="page"' : '' ?>>
          <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg>
          <?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <div class="admin-chip">
        <span class="avatar"><?= e(strtoupper(substr(admin_nama(), 0, 1))) ?></span>
        <span><strong><?= e(admin_nama()) ?></strong><small>Admin</small></span>
      </div>
      <form method="post" action="logout.php">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-keluar">Keluar</button>
      </form>
    </div>
  </aside>

  <main class="konten">
    <header class="topbar">
      <div>
        <p class="tanggal-hari"><?= e(tgl_indo(date('Y-m-d'))) ?></p>
        <h1><?= e($judul) ?></h1>
      </div>
      <a href="transaksi_form.php" class="btn btn-utama">+ Catat transaksi</a>
    </header>

    <?php if ($flash): ?>
      <div class="flash flash-<?= e($flash['tipe']) ?>" role="status"><?= e($flash['pesan']) ?></div>
    <?php endif; ?>
