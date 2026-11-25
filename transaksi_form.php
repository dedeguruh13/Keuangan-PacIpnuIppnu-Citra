<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$data = ['tanggal' => date('Y-m-d'), 'jenis' => 'masuk', 'metode' => 'cash',
         'nominal' => '', 'nama_pembayar' => '', 'keterangan' => ''];

if ($id) {
    $st = db()->prepare('SELECT * FROM transaksi WHERE id = ?');
    $st->execute([$id]);
    $ada = $st->fetch();
    if (!$ada) {
        flash('Transaksi tidak ditemukan.', 'gagal');
        redirect('transaksi.php');
    }
    $data = $ada;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data['tanggal']       = $_POST['tanggal'] ?? '';
    $data['jenis']         = $_POST['jenis'] ?? '';
    $data['metode']        = $_POST['metode'] ?? '';
    $data['nominal']       = preg_replace('/\D/', '', $_POST['nominal'] ?? ''); // terima "100.000"
    $data['nama_pembayar'] = trim($_POST['nama_pembayar'] ?? '');
    $data['keterangan']    = trim($_POST['keterangan'] ?? '');

    $tgl = DateTime::createFromFormat('Y-m-d', $data['tanggal']);
    if (!$tgl || $tgl->format('Y-m-d') !== $data['tanggal']) $errors['tanggal'] = 'Isi tanggal yang valid.';
    if (!in_array($data['jenis'], ['masuk', 'keluar'], true))  $errors['jenis'] = 'Pilih jenis transaksi.';
    if (!in_array($data['metode'], ['cash', 'qris'], true))    $errors['metode'] = 'Pilih metode pembayaran.';
    if ($data['nominal'] === '' || (int)$data['nominal'] <= 0) $errors['nominal'] = 'Isi nominal lebih dari 0.';
    if ($data['jenis'] === 'masuk' && $data['nama_pembayar'] === '') $errors['nama_pembayar'] = 'Isi nama anggota yang membayar.';
    if ($data['keterangan'] === '') $errors['keterangan'] = 'Isi keterangan transaksi.';
    if (panjang($data['nama_pembayar']) > 100) $errors['nama_pembayar'] = 'Nama maksimal 100 karakter.';
    if (panjang($data['keterangan']) > 255)    $errors['keterangan'] = 'Keterangan maksimal 255 karakter.';

    if (!$errors) {
        $nama = $data['jenis'] === 'masuk' ? $data['nama_pembayar'] : null;
        $nilai = [$data['tanggal'], $data['jenis'], $data['metode'], (int)$data['nominal'], $nama, $data['keterangan']];
        if ($id) {
            $st = db()->prepare('UPDATE transaksi SET tanggal=?, jenis=?, metode=?, nominal=?, nama_pembayar=?, keterangan=? WHERE id=?');
            $st->execute([...$nilai, $id]);
            flash('Perubahan transaksi disimpan.');
        } else {
            $st = db()->prepare('INSERT INTO transaksi (tanggal, jenis, metode, nominal, nama_pembayar, keterangan, created_by) VALUES (?,?,?,?,?,?,?)');
            $st->execute([...$nilai, $_SESSION['admin_id']]);
            flash('Transaksi dicatat.');
        }
        redirect('transaksi.php');
    }
}

function err(array $errors, string $k): string
{
    return isset($errors[$k]) ? '<p class="pesan-error" id="err-' . $k . '">' . e($errors[$k]) . '</p>' : '';
}

$judul = $id ? 'Ubah transaksi' : 'Catat transaksi';
$menu  = 'transaksi';
require __DIR__ . '/includes/header.php';
?>
<section class="kartu form-kartu">
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="grid-form">
      <fieldset class="pilihan">
        <legend>Jenis transaksi</legend>
        <label class="opsi opsi-masuk"><input type="radio" name="jenis" value="masuk" <?= $data['jenis'] === 'masuk' ? 'checked' : '' ?>><span>Pemasukan</span></label>
        <label class="opsi opsi-keluar"><input type="radio" name="jenis" value="keluar" <?= $data['jenis'] === 'keluar' ? 'checked' : '' ?>><span>Pengeluaran</span></label>
        <?= err($errors, 'jenis') ?>
      </fieldset>

      <fieldset class="pilihan">
        <legend>Metode pembayaran</legend>
        <label class="opsi opsi-cash"><input type="radio" name="metode" value="cash" <?= $data['metode'] === 'cash' ? 'checked' : '' ?>><span>Cash</span></label>
        <label class="opsi opsi-qris"><input type="radio" name="metode" value="qris" <?= $data['metode'] === 'qris' ? 'checked' : '' ?>><span>QRIS</span></label>
        <?= err($errors, 'metode') ?>
      </fieldset>

      <div class="field">
        <label for="tanggal">Tanggal</label>
        <input type="date" id="tanggal" name="tanggal" value="<?= e($data['tanggal']) ?>" required>
        <?= err($errors, 'tanggal') ?>
      </div>

      <div class="field">
        <label for="nominal">Nominal (Rp)</label>
        <input type="text" id="nominal" name="nominal" inputmode="numeric" placeholder="contoh: 100.000"
               value="<?= $data['nominal'] !== '' ? e(number_format((int)$data['nominal'], 0, ',', '.')) : '' ?>" required>
        <?= err($errors, 'nominal') ?>
      </div>

      <div class="field field-nama" id="field-nama">
        <label for="nama_pembayar">Nama anggota / pembayar</label>
        <input type="text" id="nama_pembayar" name="nama_pembayar" maxlength="100" placeholder="contoh: Ardi" value="<?= e($data['nama_pembayar'] ?? '') ?>">
        <?= err($errors, 'nama_pembayar') ?>
      </div>
    </div>

    <div class="field">
      <label for="keterangan">Keterangan</label>
      <textarea id="keterangan" name="keterangan" rows="3" maxlength="255" placeholder="contoh: Iuran anggota bulan November" required><?= e($data['keterangan']) ?></textarea>
      <?= err($errors, 'keterangan') ?>
    </div>

    <div class="tombol-form">
      <a href="transaksi.php" class="btn btn-garis">Batal</a>
      <button type="submit" class="btn btn-utama"><?= $id ? 'Simpan perubahan' : 'Simpan transaksi' ?></button>
    </div>
  </form>
</section>

<script>
// Kolom nama pembayar hanya untuk pemasukan
(function () {
  var field = document.getElementById('field-nama');
  function sesuaikan() {
    var masuk = document.querySelector('input[name="jenis"]:checked');
    field.hidden = !(masuk && masuk.value === 'masuk');
  }
  document.querySelectorAll('input[name="jenis"]').forEach(function (r) { r.addEventListener('change', sesuaikan); });
  sesuaikan();

  // Format ribuan saat mengetik nominal
  var nominal = document.getElementById('nominal');
  nominal.addEventListener('input', function () {
    var angka = nominal.value.replace(/\D/g, '');
    nominal.value = angka ? Number(angka).toLocaleString('id-ID') : '';
  });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
