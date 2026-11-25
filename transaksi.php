<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$jenis  = in_array($_GET['jenis'] ?? '', ['masuk', 'keluar'], true) ? $_GET['jenis'] : '';
$metode = in_array($_GET['metode'] ?? '', ['cash', 'qris'], true) ? $_GET['metode'] : '';
$bulan  = !empty($_GET['bulan']) ? bulan_valid($_GET['bulan']) : '';
$cari   = trim($_GET['cari'] ?? '');

$where = [];
$p = [];
if ($jenis)  { $where[] = 'jenis = ?';  $p[] = $jenis; }
if ($metode) { $where[] = 'metode = ?'; $p[] = $metode; }
if ($bulan)  { $where[] = 'tanggal BETWEEN ? AND ?'; $p[] = "$bulan-01"; $p[] = date('Y-m-t', strtotime("$bulan-01")); }
if ($cari !== '') {
    $where[] = '(nama_pembayar LIKE ? OR keterangan LIKE ?)';
    $p[] = "%$cari%";
    $p[] = "%$cari%";
}
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$st = db()->prepare("SELECT * FROM transaksi$sqlWhere ORDER BY tanggal DESC, id DESC");
$st->execute($p);
$rows = $st->fetchAll();

$totMasuk = $totKeluar = 0;
foreach ($rows as $r) {
    $r['jenis'] === 'masuk' ? $totMasuk += $r['nominal'] : $totKeluar += $r['nominal'];
}

/** Bangun URL filter dengan mengganti satu parameter. */
function url_filter(array $ganti): string
{
    $q = array_merge($_GET, $ganti);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return 'transaksi.php' . ($q ? '?' . http_build_query($q) : '');
}

$judul = 'Pemasukan & pengeluaran';
$menu  = 'transaksi';
require __DIR__ . '/includes/header.php';
?>
<section class="kartu">
  <div class="filter-bar">
    <div class="segmen" role="group" aria-label="Filter jenis">
      <?php foreach (['' => 'Semua', 'masuk' => 'Pemasukan', 'keluar' => 'Pengeluaran'] as $k => $l): ?>
        <a href="<?= e(url_filter(['jenis' => $k])) ?>" class="<?= $jenis === $k ? 'aktif' : '' ?>"<?= $jenis === $k ? ' aria-current="true"' : '' ?>><?= $l ?></a>
      <?php endforeach; ?>
    </div>
    <div class="segmen" role="group" aria-label="Filter metode">
      <?php foreach (['' => 'Semua metode', 'cash' => 'Cash', 'qris' => 'QRIS'] as $k => $l): ?>
        <a href="<?= e(url_filter(['metode' => $k])) ?>" class="<?= $metode === $k ? 'aktif' : '' ?>"<?= $metode === $k ? ' aria-current="true"' : '' ?>><?= $l ?></a>
      <?php endforeach; ?>
    </div>
    <form method="get" class="form-cari">
      <?php if ($jenis): ?><input type="hidden" name="jenis" value="<?= e($jenis) ?>"><?php endif; ?>
      <?php if ($metode): ?><input type="hidden" name="metode" value="<?= e($metode) ?>"><?php endif; ?>
      <label for="bulan" class="sr-only">Bulan</label>
      <input type="month" id="bulan" name="bulan" value="<?= e($bulan) ?>">
      <label for="cari" class="sr-only">Cari nama atau keterangan</label>
      <input type="search" id="cari" name="cari" placeholder="Cari nama / keterangan" value="<?= e($cari) ?>">
      <button type="submit" class="btn btn-garis">Terapkan</button>
      <?php if ($bulan || $cari !== ''): ?><a href="<?= e(url_filter(['bulan' => '', 'cari' => ''])) ?>" class="tautan">Reset</a><?php endif; ?>
    </form>
  </div>

  <div class="tabel-wrap">
    <table class="tabel">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Nama pembayar</th>
          <th>Keterangan</th>
          <th>Jenis</th>
          <th>Metode</th>
          <th class="kanan">Nominal</th>
          <th><span class="sr-only">Aksi</span></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $t): $masuk = $t['jenis'] === 'masuk'; ?>
        <tr>
          <td class="nowrap"><?= e(tgl_indo($t['tanggal'], true)) ?></td>
          <td class="tebal"><?= $masuk ? e($t['nama_pembayar'] ?: '—') : '—' ?></td>
          <td><?= e($t['keterangan']) ?></td>
          <td><span class="badge <?= $masuk ? 'badge-masuk' : 'badge-keluar' ?>"><?= $masuk ? 'Pemasukan' : 'Pengeluaran' ?></span></td>
          <td><span class="badge badge-<?= e($t['metode']) ?>"><?= $t['metode'] === 'cash' ? 'Cash' : 'QRIS' ?></span></td>
          <td class="kanan nowrap tebal <?= $masuk ? 'teks-masuk' : 'teks-keluar' ?>"><?= $masuk ? '+' : '−' ?> <?= rupiah($t['nominal']) ?></td>
          <td class="aksi">
            <a href="transaksi_form.php?id=<?= (int)$t['id'] ?>" class="tautan">Ubah</a>
            <form method="post" action="hapus.php" onsubmit="return confirm('Hapus transaksi ini? Saldo akan dihitung ulang.');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button type="submit" class="tautan tautan-hapus">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$rows): ?>
    <p class="kosong">Tidak ada transaksi untuk filter ini.</p>
  <?php endif; ?>

  <div class="total-bawah">
    <span>Total masuk <b class="teks-masuk"><?= rupiah($totMasuk) ?></b></span>
    <span>Total keluar <b class="teks-keluar"><?= rupiah($totKeluar) ?></b></span>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
