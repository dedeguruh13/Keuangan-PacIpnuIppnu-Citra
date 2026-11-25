<?php
/**
 * Reset akun admin -> username: admin, password: admin123
 * Taruh di folder keuangan-pac, buka sekali di browser, lalu HAPUS file ini.
 */
require __DIR__ . '/includes/bootstrap.php';

$jumlah = db()->query('SELECT COUNT(*) FROM admin')->fetchColumn();
echo "Jumlah akun sebelum reset: $jumlah<br>";

db()->exec("DELETE FROM admin WHERE username = 'admin'");
db()->prepare("INSERT INTO admin (username, nama, password_hash) VALUES ('admin', 'Admin Bendahara', ?)")
    ->execute([password_hash('admin123', PASSWORD_DEFAULT)]);

unset($_SESSION['login_gagal'], $_SESSION['login_gagal_waktu']);

$cek = db()->query("SELECT password_hash FROM admin WHERE username = 'admin'")->fetchColumn();
echo password_verify('admin123', $cek)
    ? 'Berhasil! Login dengan username <b>admin</b> dan password <b>admin123</b>.<br>Jangan lupa hapus file reset_admin.php ini.'
    : 'Masih gagal, kirim screenshot halaman ini.';