<?php
require __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Batasi percobaan: 5 kali gagal -> tunggu 60 detik
    $gagal = $_SESSION['login_gagal'] ?? 0;
    $sejak = $_SESSION['login_gagal_waktu'] ?? 0;
    if ($gagal >= 5 && time() - $sejak < 60) {
        $error = 'Terlalu banyak percobaan. Coba lagi dalam ' . (60 - (time() - $sejak)) . ' detik.';
    } else {
        if ($gagal >= 5) { $gagal = 0; }
        $st = db()->prepare('SELECT id, nama, password_hash FROM admin WHERE username = ?');
        $st->execute([$username]);
        $admin = $st->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = (int)$admin['id'];
            $_SESSION['admin_nama'] = $admin['nama'];
            unset($_SESSION['login_gagal'], $_SESSION['login_gagal_waktu']);
            redirect('dashboard.php');
        }
        $_SESSION['login_gagal'] = $gagal + 1;
        $_SESSION['login_gagal_waktu'] = time();
        $error = 'Username atau kata sandi salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk Admin · Keuangan PAC IPNU IPPNU Cikarang Utara</title>
<link rel="icon" href="assets/img/logo.jpg">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="halaman-login">
  <section class="login-kiri">
    <img src="assets/img/logo.jpg" alt="Logo PAC IPNU IPPNU Cikarang Utara" class="login-logo">
    <h1>Sistem Keuangan<br>PAC IPNU IPPNU Cikarang Utara</h1>
    <p>Catat pemasukan dan pengeluaran, pantau saldo Cash dan QRIS, lalu unduh laporan bulanan dalam PDF.</p>
  </section>
  <section class="login-kanan">
    <form method="post" class="login-form" novalidate>
      <h2>Masuk sebagai admin</h2>
      <p class="muted">Halaman ini khusus admin keuangan PAC.</p>
      <?php if ($error): ?><div class="flash flash-gagal" role="alert"><?= e($error) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <input id="username" name="username" type="text" autocomplete="username" required value="<?= e($username) ?>" autofocus>
      <label for="password">Kata sandi</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
      <button type="submit" class="btn btn-utama btn-penuh">Masuk</button>
    </form>
  </section>
</body>
</html>
