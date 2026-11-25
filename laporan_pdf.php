<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/lib/fpdf/fpdf.php';

$bulan = bulan_valid($_GET['bulan'] ?? null);
$lap   = data_laporan($bulan);
$r     = $lap['ringkasan'];

/** FPDF memakai encoding Windows-1252, ubah dari UTF-8. */
function t(string $s): string
{
    $s = str_replace(['—', '–', '−'], '-', $s);
    return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);
}

function rp(int $n): string
{
    return ($n < 0 ? '-' : '') . number_format(abs($n), 0, ',', '.');
}

class LaporanPDF extends FPDF
{
    public array $lebar = [];
    public array $rata  = [];
    public array $judulKolom = [];
    public string $periode = '';

    function Footer()
    {
        $this->SetY(-12);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(110);
        $this->Cell(0, 5, t('Laporan Keuangan PAC IPNU IPPNU Cikarang Utara - ' . $this->periode), 0, 0, 'L');
        $this->Cell(0, 5, 'Halaman ' . $this->PageNo() . ' dari {nb}', 0, 0, 'R');
        $this->SetTextColor(0);
    }

    function SisaRuang(): float
    {
        return $this->PageBreakTrigger - $this->GetY();
    }

    function KepalaTabel()
    {
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetFillColor(230, 235, 243);
        $this->SetDrawColor(170, 180, 195);
        foreach ($this->judulKolom as $i => $j) {
            $this->Cell($this->lebar[$i], 7, t($j), 1, 0, $this->rata[$i], true);
        }
        $this->Ln();
        $this->SetFont('Helvetica', '', 9);
    }

    /** Hitung jumlah baris yang dibutuhkan MultiCell untuk teks pada lebar tertentu. */
    function JumlahBaris(float $w, string $txt): int
    {
        $cw = $this->CurrentFont['cw'];
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c === ' ') $sep = $i;
            $l += $cw[$c] ?? 500;
            if ($l > $wmax) {
                if ($sep === -1) { if ($i === $j) $i++; }
                else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }

    function Baris(array $data, bool $tebal = false, bool $isi = false)
    {
        $this->SetFont('Helvetica', $tebal ? 'B' : '', 9);
        $nb = 1;
        foreach ($data as $i => $d) {
            $nb = max($nb, $this->JumlahBaris($this->lebar[$i], $d));
        }
        $h = 5 * $nb + 1;
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage();
            $this->KepalaTabel();
            $this->SetFont('Helvetica', $tebal ? 'B' : '', 9);
        }
        foreach ($data as $i => $d) {
            $x = $this->GetX();
            $y = $this->GetY();
            if ($isi) {
                $this->SetFillColor(230, 235, 243);
                $this->Rect($x, $y, $this->lebar[$i], $h, 'F');
            }
            $this->Rect($x, $y, $this->lebar[$i], $h);
            $this->SetXY($x, $y + 0.5);
            $this->MultiCell($this->lebar[$i], 5, $d, 0, $this->rata[$i]);
            $this->SetXY($x + $this->lebar[$i], $y);
        }
        $this->Ln($h);
    }
}

$pdf = new LaporanPDF('P', 'mm', 'A4');
$pdf->periode = nama_bulan($bulan);
$pdf->AliasNbPages();
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 18);
$pdf->SetTitle(t('Laporan Keuangan ' . nama_bulan($bulan)));
$pdf->SetAuthor(t('PAC IPNU IPPNU Cikarang Utara'));
$pdf->AddPage();

/* ---------- Kop ---------- */
$pdf->Image(__DIR__ . '/assets/img/logo.jpg', 15, 12, 0, 24);
$pdf->SetXY(40, 13);
$pdf->SetFont('Helvetica', 'B', 14);
$pdf->Cell(0, 7, t(ORG_NAMA), 0, 2);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 6, t(ORG_WILAYAH), 0, 2);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(0, 5, t(ORG_ALAMAT), 0, 2);
$pdf->SetLineWidth(0.8);
$pdf->SetDrawColor(31, 53, 87);
$pdf->Line(15, 39, 195, 39);
$pdf->SetLineWidth(0.2);

$pdf->SetY(45);
$pdf->SetFont('Helvetica', 'B', 13);
$pdf->Cell(0, 7, t('LAPORAN KEUANGAN BULANAN'), 0, 1, 'C');
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(0, 5, t('Periode ' . tgl_indo($lap['dari']) . ' - ' . tgl_indo($lap['sampai'])), 0, 1, 'C');
$pdf->Ln(5);

/* ---------- Ringkasan ---------- */
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Ringkasan', 0, 1);
$pdf->lebar = [60, 40, 40, 40];
$pdf->rata  = ['L', 'R', 'R', 'R'];
$pdf->judulKolom = ['Uraian', 'Cash (Rp)', 'QRIS (Rp)', 'Total (Rp)'];
$pdf->KepalaTabel();
foreach ([
    ['Saldo awal bulan', 'awal'],
    ['Pemasukan', 'masuk'],
    ['Pengeluaran', 'keluar'],
] as [$label, $k]) {
    $pdf->Baris([t($label), rp($r['cash'][$k]), rp($r['qris'][$k]), rp($r['total'][$k])]);
}
$pdf->Baris([t('Saldo akhir bulan'), rp($r['cash']['akhir']), rp($r['qris']['akhir']), rp($r['total']['akhir'])], true, true);
$pdf->Ln(6);

/* ---------- Rincian ---------- */
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Rincian transaksi', 0, 1);
$pdf->lebar = [8, 20, 34, 54, 14, 25, 25];
$pdf->rata  = ['C', 'L', 'L', 'L', 'C', 'R', 'R'];
$pdf->judulKolom = ['No', 'Tanggal', 'Nama Pembayar', 'Keterangan', 'Metode', 'Masuk (Rp)', 'Keluar (Rp)'];
$pdf->KepalaTabel();

if (!$lap['baris']) {
    $pdf->Cell(180, 8, t('Tidak ada transaksi pada periode ini.'), 1, 1, 'C');
} else {
    foreach ($lap['baris'] as $i => $tr) {
        $masuk = $tr['jenis'] === 'masuk';
        $pdf->Baris([
            (string)($i + 1),
            t(tgl_indo($tr['tanggal'], true)),
            t($masuk ? ($tr['nama_pembayar'] ?: '-') : '-'),
            t($tr['keterangan']),
            $tr['metode'] === 'cash' ? 'Cash' : 'QRIS',
            $masuk ? rp((int)$tr['nominal']) : '-',
            $masuk ? '-' : rp((int)$tr['nominal']),
        ]);
    }
    $pdf->lebar = [130, 25, 25];
    $pdf->rata  = ['R', 'R', 'R'];
    $pdf->Baris(['Jumlah', rp($r['total']['masuk']), rp($r['total']['keluar'])], true, true);
}

/* ---------- Tanda tangan ---------- */
if ($pdf->SisaRuang() < 50) {
    $pdf->AddPage();
}
$pdf->Ln(10);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(0, 5, t(KOTA_TTD . ', ' . tgl_indo(date('Y-m-d'))), 0, 1, 'R');
$pdf->Ln(3);
$kolom = [['Ketua PAC IPNU', NAMA_KETUA_IPNU], ['Ketua PAC IPPNU', NAMA_KETUA_IPPNU], ['Bendahara', NAMA_BENDAHARA]];
$y = $pdf->GetY();
foreach ($kolom as $i => [$jabatan, $nama]) {
    $x = 15 + $i * 60;
    $pdf->SetXY($x, $y);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(60, 5, t($jabatan), 0, 2, 'C');
    $pdf->SetXY($x, $y + 25);
    $pdf->SetFont('Helvetica', 'BU', 10);
    $pdf->Cell(60, 5, t($nama), 0, 0, 'C');
}

$pdf->Output('I', 'Laporan-Keuangan-PAC-' . $bulan . '.pdf');
