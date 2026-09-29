<?php
session_start();
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}
set_include_path(ROOT);

require ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/AdvanceRequest.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/Model/ActivityLogModel.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/security.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

// Oturum ve Yetki Kontrolü
if (!isset($_SESSION['firm_id'], $_SESSION['user'])) {
    die("Yetkisiz erişim.");
}

$firm_id = (int)$_SESSION['firm_id'];
$Auths = new Auths();
if (!$Auths->Authorize('avans_talepleri')) {
    http_response_code(403);
    die("Bu işlem için yetkiniz yok.");
}

$advanceModel = new AdvanceRequest();
$requests = $advanceModel->getRequestsByFirm($firm_id);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Avans Talepleri');

// Başlık ve Firma Bilgisi
$firmName = $_SESSION['firm_name'] ?? 'Firma';
$sheet->setCellValue('A1', $firmName . ' - AVANS TALEPLERİ LİSTESİ');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1E293B');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension(1)->setRowHeight(30);

// Sütun Başlıkları
$headers = [
    'A2' => 'Sıra',
    'B2' => 'Talep ID',
    'C2' => 'Personel Adı Soyadı',
    'D2' => 'Talep Tutarı (₺)',
    'E2' => 'Hedef Dönem',
    'F2' => 'Talep Tarihi',
    'G2' => 'İşlem / Onay Tarihi',
    'H2' => 'Durum',
    'I2' => 'İşlemi Yapan',
    'J2' => 'Açıklama / Not'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1E3A8A']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CBD5E1']
        ]
    ]
];
$sheet->getStyle('A2:J2')->applyFromArray($headerStyle);
$sheet->getRowDimension(2)->setRowHeight(26);

// Veri Satırları
$row = 3;
$index = 1;

foreach ($requests as $req) {
    $statusText = 'Beklemede';
    if ($req->durum == 1) {
        $statusText = 'Onaylandı';
    } elseif ($req->durum == 2) {
        $statusText = 'Reddedildi';
    }

    $donem = Date::monthName($req->hedef_ay) . ' ' . $req->hedef_yil;
    $islemTarihi = ($req->durum != 0 && !empty($req->formatted_updated_at)) ? $req->formatted_updated_at : '-';
    $islemYapan = !empty($req->processed_by_name) ? $req->processed_by_name : '-';
    $aciklama = $req->aciklama ?? '';
    if (!empty($req->admin_note)) {
        $aciklama .= ($aciklama ? ' | Yönetici Notu: ' : 'Yönetici Notu: ') . $req->admin_note;
    }

    $sheet->setCellValue('A' . $row, $index);
    $sheet->setCellValue('B' . $row, '#' . $req->id);
    $sheet->setCellValue('C' . $row, $req->full_name);
    $sheet->setCellValue('D' . $row, (float)$req->tutar);
    $sheet->setCellValue('E' . $row, $donem);
    $sheet->setCellValue('F' . $row, $req->formatted_date);
    $sheet->setCellValue('G' . $row, $islemTarihi);
    $sheet->setCellValue('H' . $row, $statusText);
    $sheet->setCellValue('I' . $row, $islemYapan);
    $sheet->setCellValue('J' . $row, $aciklama);

    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00 "₺"');
    $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Duruma göre satır renklendirme
    if ($req->durum == 1) {
        $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('16A34A')->setBold(true);
    } elseif ($req->durum == 2) {
        $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('DC2626')->setBold(true);
    } else {
        $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('D97706')->setBold(true);
    }

    $row++;
    $index++;
}

// Genel Kenarlıklar
$lastRow = $row - 1;
if ($lastRow >= 3) {
    $sheet->getStyle('A3:J' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
}

// Otomatik Sütun Genişlikleri
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// İşlem Logu
ActivityLogModel::log('avans_talepleri', 'excel_export', 'Avans talepleri listesi Excel olarak dışa aktarıldı.');

// İndirme Başlıkları
$filename = 'Avans_Talepleri_' . date('Y-m-d_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
