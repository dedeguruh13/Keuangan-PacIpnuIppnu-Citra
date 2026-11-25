<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

date_default_timezone_set('Asia/Jakarta');

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

/* ---------- helper tampilan ---------- */

/** Panjang teks (aman walau ekstensi mbstring tidak aktif). */
function panjang(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function rupiah($n): string
{
    $n = (int)$n;
    return ($n < 0 ? '-' : '') . 'Rp ' . number_format(abs($n), 0, ',', '.');
}

const BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
               'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function tgl_indo(string $date, bool $pendek = false): string
{
    [$y, $m, $d] = explode('-', substr($date, 0, 10));
    $b = BULAN[(int)$m];
    return (int)$d . ' ' . ($pendek ? substr($b, 0, 3) : $b) . ' ' . $y;
}

function nama_bulan(string $ym): string
{
    [$y, $m] = explode('-', $ym);
    return BULAN[(int)$m] . ' ' . $y;
}

/** Validasi format YYYY-MM, kembalikan bulan berjalan bila tidak valid. */
function bulan_valid(?string $ym): string
{
    return ($ym && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym)) ? $ym : date('Y-m');
}

/* ---------- autentikasi ---------- */

function require_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function admin_nama(): string
{
    return $_SESSION['admin_nama'] ?? 'Admin';
}

/* ---------- CSRF & flash ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

function flash(string $pesan, string $tipe = 'sukses'): void
{
    $_SESSION['flash'] = ['pesan' => $pesan, 'tipe' => $tipe];
}

function ambil_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------- perhitungan saldo ---------- */

/**
 * Ringkasan masuk/keluar per metode dalam rentang tanggal (inklusif).
 * $dari / $sampai boleh null (tanpa batas).
 * Hasil: ['cash'=>['masuk'=>..,'keluar'=>..], 'qris'=>[...]]
 */
function ringkasan(?string $dari = null, ?string $sampai = null): array
{
    $sql = "SELECT metode,
                   COALESCE(SUM(CASE WHEN jenis='masuk'  THEN nominal END),0) AS masuk,
                   COALESCE(SUM(CASE WHEN jenis='keluar' THEN nominal END),0) AS keluar
            FROM transaksi WHERE 1=1";
    $p = [];
    if ($dari)   { $sql .= ' AND tanggal >= ?'; $p[] = $dari; }
    if ($sampai) { $sql .= ' AND tanggal <= ?'; $p[] = $sampai; }
    $sql .= ' GROUP BY metode';

    $hasil = ['cash' => ['masuk' => 0, 'keluar' => 0], 'qris' => ['masuk' => 0, 'keluar' => 0]];
    $st = db()->prepare($sql);
    $st->execute($p);
    foreach ($st as $r) {
        $hasil[$r['metode']] = ['masuk' => (int)$r['masuk'], 'keluar' => (int)$r['keluar']];
    }
    return $hasil;
}

/** Data lengkap laporan satu bulan (dipakai halaman laporan & PDF). */
function data_laporan(string $ym): array
{
    $awalBulan  = $ym . '-01';
    $akhirBulan = date('Y-m-t', strtotime($awalBulan));
    $sebelum    = date('Y-m-d', strtotime($awalBulan . ' -1 day'));

    $lalu  = ringkasan(null, $sebelum);
    $bulan = ringkasan($awalBulan, $akhirBulan);

    $r = [];
    foreach (['cash', 'qris'] as $m) {
        $awal = $lalu[$m]['masuk'] - $lalu[$m]['keluar'];
        $r[$m] = [
            'awal'   => $awal,
            'masuk'  => $bulan[$m]['masuk'],
            'keluar' => $bulan[$m]['keluar'],
            'akhir'  => $awal + $bulan[$m]['masuk'] - $bulan[$m]['keluar'],
        ];
    }
    foreach (['awal', 'masuk', 'keluar', 'akhir'] as $k) {
        $r['total'][$k] = $r['cash'][$k] + $r['qris'][$k];
    }

    $st = db()->prepare('SELECT * FROM transaksi WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal, id');
    $st->execute([$awalBulan, $akhirBulan]);

    return ['ringkasan' => $r, 'baris' => $st->fetchAll(), 'dari' => $awalBulan, 'sampai' => $akhirBulan];
}
