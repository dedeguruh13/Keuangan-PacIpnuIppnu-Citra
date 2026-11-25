<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$st = db()->prepare('SELECT id, username, nama, password_hash FROM admin WHERE id = ?');
$st->execute([$_SESSION['admin_id']]);
$admin = $st->fetch();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nama  = trim($_POST['nama'] ?? '');
    $lama  = $_POST['password_lama'] ?? '';
    $baru  = $_POST['password_baru'] ?? '';
    $ulang = $_POST['password_ulang'] ?? '';

    if ($nama === '') $errors[] = 'Nama tampilan wajib diisi.';
    if (!password_verify($lama, $admin['password_hash'])) $errors[] = 'Kata sandi saat ini salah.';
    if ($baru !== '' && strlen($baru) < 8) $errors[] = 'Kata sandi baru minimal 8 karakter.';
    if ($baru !== $ulang) $errors[] = 'Ulangi kata sandi baru dengan sama persis.';

    if (!$errors) {
        if ($baru !== '') {
            db()->prepare('UPDATE admin SET nama = ?, password_hash = ? WHERE id = ?')
                ->execute([$nama, password_hash($baru, PASSWORD_DEFAULT), $admin['id']]);
        } else {
            db()->prepare('UPDATE admin SET nama = ? WHERE id = ?')->execute([$nama, $admin['id']]);
        }
        $_SESSION['admin_nama'] = $nama;
        flash('Akun diperbarui.');
        redirect('akun.php');
    }
    $admin['nama'] = $nama;
}

$judul = 'Akun admin';
$menu  = 'akun';
require __DIR__ . '/includes/header.php';
?>
<section class="kartu form-kartu sempit">
  <?php if ($errors): ?>
    <div class="flash flash-gagal" role="alert"><?= implode('<br>', array_map('e', $errors)) ?></div>
  <?php endif; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label>Username</label>
      <p class="nilai-tetap"><?= e($admin['username']) ?></p>
    </div>
    <div class="field">
      <label for="nama">Nama tampilan</label>
      <input type="text" id="nama" name="nama" maxlength="100" value="<?= e($admin['nama']) ?>" required>
    </div>
    <div class="field">
      <label for="password_baru">Kata sandi baru <span class="muted">(kosongkan jika tidak diganti)</span></label>
      <input type="password" id="password_baru" name="password_baru" autocomplete="new-password" minlength="8">
    </div>
    <div class="field">
      <label for="password_ulang">Ulangi kata sandi baru</label>
      <input type="password" id="password_ulang" name="password_ulang" autocomplete="new-password">
    </div>
    <div class="field">
      <label for="password_lama">Kata sandi saat ini <span class="muted">(wajib, untuk konfirmasi)</span></label>
      <input type="password" id="password_lama" name="password_lama" autocomplete="current-password" required>
    </div>
    <div class="tombol-form">
      <button type="submit" class="btn btn-utama">Simpan akun</button>
    </div>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
