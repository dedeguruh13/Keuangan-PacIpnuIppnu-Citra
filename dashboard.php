<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$semua = ringkasan();
$saldoCash = $semua['cash']['masuk'] - $semua['cash']['keluar'];
$saldoQris = $semua['qris']['masuk'] - $semua['qris']['keluar'];
$total     = $saldoCash + $saldoQris;

$pos      = max($saldoCash, 0) + max($saldoQris, 0);
$persenCash = $pos > 0 ? (int)round(max($saldoCash, 0) / $pos * 100) : 50;

$bulanIni = ringkasan(date('Y-m-01'), date('Y-m-t'));
$masukBulanIni  = $bulanIni['cash']['masuk']  + $bulanIni['qris']['masuk'];
$keluarBulanIni = $bulanIni['cash']['keluar'] + $bulanIni['qris']['keluar'];

$terbaru = db()->query('SELECT * FROM transaksi ORDER BY tanggal DESC, id DESC LIMIT 6')->fetchAll();

$judul = 'Dashboard keuangan';
$menu  = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="grid-saldo">
  <article class="kartu kartu-total">
    <h2>Total saldo kas</h2>
    <p class="angka-besar"><?= rupiah($total) ?></p>
    <div class="bar-komposisi" aria-hidden="true">
      <span class="bar-cash" style="width: <?= $persenCash ?>%"></span>
      <span class="bar-qris" style="width: <?= 100 - $persenCash ?>%"></span>
    </div>
    <p class="keterangan-bar">Cash <?= $persenCash ?>% · QRIS <?= 100 - $persenCash ?>%</p>
  </article>

  <article class="kartu">
    <h2 class="judul-metode"><span class="ikon ikon-cash" aria-hidden="true">
      <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg></span>Saldo Cash</h2>
    <p class="angka"><?= rupiah($saldoCash) ?></p>
    <p class="rincian">Masuk <b class="teks-masuk"><?= rupiah($semua['cash']['masuk']) ?></b></p>
    <p class="rincian">Keluar <b class="teks-keluar"><?= rupiah($semua['cash']['keluar']) ?></b></p>
  </article>

  <article class="kartu">
    <h2 class="judul-metode"><span class="ikon ikon-qris" aria-hidden="true">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v7h-4"/></svg></span>Saldo QRIS</h2>
    <p class="angka"><?= rupiah($saldoQris) ?></p>
    <p class="rincian">Masuk <b class="teks-masuk"><?= rupiah($semua['qris']['masuk']) ?></b></p>
    <p class="rincian">Keluar <b class="teks-keluar"><?= rupiah($semua['qris']['keluar']) ?></b></p>
  </article>
</section>

<section class="grid-bulan">
  <article class="kartu kecil">
    <h2>Pemasukan <?= e(nama_bulan(date('Y-m'))) ?></h2>
    <p class="angka teks-masuk">+ <?= rupiah($masukBulanIni) ?></p>
  </article>
  <article class="kartu kecil">
    <h2>Pengeluaran <?= e(nama_bulan(date('Y-m'))) ?></h2>
    <p class="angka teks-keluar">− <?= rupiah($keluarBulanIni) ?></p>
  </article>
  <article class="kartu kecil">
    <h2>Selisih bulan ini</h2>
    <p class="angka"><?= rupiah($masukBulanIni - $keluarBulanIni) ?></p>
  </article>
</section>

<section class="kartu">
  <div class="kepala-kartu">
    <h2 class="judul-bagian">Transaksi terbaru</h2>
    <a href="transaksi.php" class="tautan">Lihat semua</a>
  </div>
  <?php if (!$terbaru): ?>
    <p class="kosong">Belum ada transaksi. Mulai dengan menekan <a href="transaksi_form.php">Catat transaksi</a>.</p>
  <?php else: ?>
    <ul class="daftar-terbaru">
      <?php foreach ($terbaru as $t): $masuk = $t['jenis'] === 'masuk'; ?>
        <li>
          <span class="tanda <?= $masuk ? 'tanda-masuk' : 'tanda-keluar' ?>" aria-hidden="true"><?= $masuk ? '↓' : '↑' ?></span>
          <div class="isi">
            <strong><?= e($t['keterangan']) ?></strong>
            <small><?= e(tgl_indo($t['tanggal'], true)) ?> · <?= $masuk ? e($t['nama_pembayar'] ?: 'Pemasukan') : 'Pengeluaran' ?></small>
          </div>
          <span class="badge badge-<?= e($t['metode']) ?>"><?= $t['metode'] === 'cash' ? 'Cash' : 'QRIS' ?></span>
          <span class="nominal <?= $masuk ? 'teks-masuk' : 'teks-keluar' ?>"><?= $masuk ? '+' : '−' ?> <?= rupiah($t['nominal']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
