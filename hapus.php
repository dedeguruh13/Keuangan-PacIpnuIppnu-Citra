<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('transaksi.php');
}
csrf_check();

$st = db()->prepare('DELETE FROM transaksi WHERE id = ?');
$st->execute([(int)($_POST['id'] ?? 0)]);
flash($st->rowCount() ? 'Transaksi dihapus.' : 'Transaksi tidak ditemukan.', $st->rowCount() ? 'sukses' : 'gagal');
redirect('transaksi.php');
