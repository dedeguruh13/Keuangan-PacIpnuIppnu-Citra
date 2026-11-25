<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$bulan = bulan_valid($_GET['bulan'] ?? null);
$lap   = data_laporan($bulan);
$r     = $lap['ringkasan'];

$judul = 'Laporan bulanan';
$menu  = 'laporan';
require __DIR__ . '/includes/header.php';
?>
<div class="laporan-aksi">
  <form method="get" class="form-cari">
    <label for="bulan">Periode</label>
    <input type="month" id="bulan" name="bulan" value="<?= e($bulan) ?>" onchange="this.form.submit()">
    <noscript><button type="submit" class="btn btn-garis">Tampilkan</button></noscript>
  </form>
  <a href="laporan_pdf.php?bulan=<?= e($bulan) ?>" class="btn btn-utama" target="_blank" rel="noopener">
    <svg viewBox="0 0 24 24" aria-hidden="true" class="ikon-btn"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
    Unduh PDF
  </a>
</div>

<section class="kartu">
  <h2 class="judul-bagian">Ringkasan <?= e(nama_bulan($bulan)) ?></h2>
  <div class="tabel-wrap">
    <table class="tabel tabel-ringkasan">
      <thead>
        <tr><th>Uraian</th><th class="kanan">Cash</th><th class="kanan">QRIS</th><th class="kanan">Total</th></tr>
      </thead>
      <tbody>
        <tr><td>Saldo awal bulan</td><td class="kanan"><?= rupiah($r['cash']['awal']) ?></td><td class="kanan"><?= rupiah($r['qris']['awal']) ?></td><td class="kanan tebal"><?= rupiah($r['total']['awal']) ?></td></tr>
        <tr><td>Pemasukan</td><td class="kanan teks-masuk"><?= rupiah($r['cash']['masuk']) ?></td><td class="kanan teks-masuk"><?= rupiah($r['qris']['masuk']) ?></td><td class="kanan tebal teks-masuk"><?= rupiah($r['total']['masuk']) ?></td></tr>
        <tr><td>Pengeluaran</td><td class="kanan teks-keluar"><?= rupiah($r['cash']['keluar']) ?></td><td class="kanan teks-keluar"><?= rupiah($r['qris']['keluar']) ?></td><td class="kanan tebal teks-keluar"><?= rupiah($r['total']['keluar']) ?></td></tr>
        <tr class="baris-akhir"><td>Saldo akhir bulan</td><td class="kanan"><?= rupiah($r['cash']['akhir']) ?></td><td class="kanan"><?= rupiah($r['qris']['akhir']) ?></td><td class="kanan"><?= rupiah($r['total']['akhir']) ?></td></tr>
      </tbody>
    </table>
  </div>
</section>

<section class="kartu">
  <h2 class="judul-bagian">Rincian transaksi</h2>
  <?php if (!$lap['baris']): ?>
    <p class="kosong">Belum ada transaksi di <?= e(nama_bulan($bulan)) ?>.</p>
  <?php else: ?>
  <div class="tabel-wrap">
    <table class="tabel">
      <thead>
        <tr><th>No</th><th>Tanggal</th><th>Nama pembayar</th><th>Keterangan</th><th>Metode</th><th class="kanan">Masuk</th><th class="kanan">Keluar</th></tr>
      </thead>
      <tbody>
      <?php foreach ($lap['baris'] as $i => $t): $masuk = $t['jenis'] === 'masuk'; ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td class="nowrap"><?= e(tgl_indo($t['tanggal'], true)) ?></td>
          <td class="tebal"><?= $masuk ? e($t['nama_pembayar'] ?: '—') : '—' ?></td>
          <td><?= e($t['keterangan']) ?></td>
          <td><?= $t['metode'] === 'cash' ? 'Cash' : 'QRIS' ?></td>
          <td class="kanan nowrap teks-masuk"><?= $masuk ? rupiah($t['nominal']) : '—' ?></td>
          <td class="kanan nowrap teks-keluar"><?= $masuk ? '—' : rupiah($t['nominal']) ?></td>
        </tr>
      <?php endforeach; ?>
        <tr class="baris-akhir">
          <td colspan="5" class="kanan">Jumlah</td>
          <td class="kanan nowrap"><?= rupiah($r['total']['masuk']) ?></td>
          <td class="kanan nowrap"><?= rupiah($r['total']['keluar']) ?></td>
        </tr>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
