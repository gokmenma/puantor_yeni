<?php
session_start();
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/PersonIcra.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/Model/ActivityLogModel.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Session & Yetki Kontrolü
if (!isset($_SESSION['firm_id'], $_SESSION['user'])) {
    die("Yetkisiz erişim.");
}

$firm_id = $_SESSION['firm_id'];
$Auths = new Auths();
if (!$Auths->Authorize('icra_files_list') && !$Auths->Authorize('person_page_icra_info')) {
    http_response_code(403);
    die("Bu işlem için yetkiniz yok.");
}

$Persons = new Persons();
$PersonIcra = new PersonIcra();

$id_param = $_GET['id'] ?? $_GET['file_id'] ?? '';
$file_id = is_numeric($id_param) ? (int)$id_param : (!empty($id_param) ? (int)Security::decrypt($id_param) : 0);
$person_id_param = $_GET['person_id'] ?? '';
$person_id = is_numeric($person_id_param) ? (int)$person_id_param : (!empty($person_id_param) ? (int)Security::decrypt($person_id_param) : 0);

if ($file_id <= 0 && $person_id > 0) {
    $person_files = $PersonIcra->getByPersonId($person_id);
    if (!empty($person_files)) {
        $file_id = (int)$person_files[0]->id;
    }
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 1. TEK İCRA DOSYASI İÇİN KESİNTİLER VE BİLGİLER DETAY EXCEL'İ
if ($file_id > 0) {
    $icra_file = $PersonIcra->find($file_id);
    if (!$icra_file || $icra_file->deleted_at !== null) {
        die("İcra dosyası bulunamadı.");
    }

    $person = $Persons->find($icra_file->person_id);
    if (!$person || $person->firm_id != $firm_id) {
        die("Yetkisiz işlem.");
    }

    $sheet->setTitle('İcra Dosya Detayı');

    // Başlık Stil Bloğu
    $sheet->setCellValue('A1', 'İCRA DOSYASI VE KESİNTİ GEÇMİŞİ RAPORU');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E293B'));
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

    // Genel İcra Dosya Bilgileri
    $decryptedKimlik = Security::safeDecrypt($person->kimlik_no ?? '');

    $info = [
        ['Personel Adı Soyadı:', $person->full_name, '', 'İcra Dairesi:', $icra_file->icra_dairesi],
        ['TC Kimlik No:', $decryptedKimlik ?: '-', '', 'Dosya No:', $icra_file->dosya_no],
        ['Sicil No:', $person->sigorta_no ?? '-', '', 'Alacaklı / Avukat:', $icra_file->alacakli],
        ['İcra Sırası:', $icra_file->icra_sirasi . '. Sıra', '', 'Toplam Borç Tutarı:', (float)$icra_file->toplam_borc],
        ['Kesinti Yöntemi:', ($icra_file->kesinti_yontemi === 'sabit' ? 'Sabit Tutar' : 'Maaş Oranı (%25 vb.)'), '', 'Kesinti Oranı / Tutarı:', ($icra_file->kesinti_yontemi === 'sabit' ? (float)$icra_file->kesinti_tutari : ($icra_file->kesinti_orani ?: '%25'))],
        ['Durum:', $icra_file->durum, '', 'Başlama / Bitiş Tarihi:', ($icra_file->baslama_tarihi ? Date::dmY($icra_file->baslama_tarihi) : '-') . ' / ' . ($icra_file->bitis_tarihi ? Date::dmY($icra_file->bitis_tarihi) : '-')],
        ['Gelen Evrak No/Tarih:', $icra_file->gelen_evrak ?: '-', '', 'Giden Evrak No/Tarih:', $icra_file->giden_evrak ?: '-'],
        ['Açıklama / Notlar:', $icra_file->aciklama ?: '-', '', '', '']
    ];

    $row = 3;
    foreach ($info as $item) {
        $sheet->setCellValue('A' . $row, $item[0]);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('B' . $row, $item[1]);

        if (!empty($item[3])) {
            $sheet->setCellValue('D' . $row, $item[3]);
            $sheet->getStyle('D' . $row)->getFont()->setBold(true);
            $sheet->setCellValue('E' . $row, $item[4]);

            if (is_float($item[4]) || is_int($item[4])) {
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
            }
        }

        if (is_float($item[1]) || is_int($item[1])) {
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
        }
        $row++;
    }

    $row += 1;
    // Kesinti Geçmişi Tablo Başlığı
    $sheet->setCellValue('A' . $row, 'YAPILAN BORDRO KESİNTİLERİ GEÇMİŞİ');
    $sheet->mergeCells('A' . $row . ':E' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
    $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF206BC4');
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    $row++;
    $headers = ['Sıra', 'Dönem (Ay/Yıl)', 'Açıklama / Kesinti Türü', 'Kayıt Tarihi', 'Kesinti Tutarı (₺)'];
    $cols = ['A', 'B', 'C', 'D', 'E'];
    foreach ($headers as $idx => $text) {
        $cell = $cols[$idx] . $row;
        $sheet->setCellValue($cell, $text);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
    }

    // Kesinti verilerini çek
    $history = $PersonIcra->getDeductionsHistory($person->id, $icra_file->dosya_no);
    $tableStartRow = $row + 1;
    $row++;
    $i = 1;
    $total_sum = 0.0;

    if (empty($history)) {
        $sheet->setCellValue('A' . $row, 'Bu icra dosyasına ait yapılmış bir bordro kesintisi bulunmamaktadır.');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
    } else {
        foreach ($history as $h) {
            $tutar = (float)$h->tutar;
            $total_sum += $tutar;
            $donem = sprintf('%02d/%04d', (int)$h->ay, (int)$h->yil);

            $sheet->setCellValue('A' . $row, $i++);
            $sheet->setCellValue('B' . $row, $donem);
            $sheet->setCellValue('C' . $row, $h->aciklama ?: ($h->turu ?? 'İcra Kesintisi'));
            $sheet->setCellValue('D' . $row, !empty($h->created_at) ? Date::dmYHis($h->created_at) : '-');
            $sheet->setCellValue('E' . $row, $tutar);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
            $row++;
        }

        // Toplam Satırı
        $sheet->setCellValue('A' . $row, 'TOPLAM YAPILAN KESİNTİ');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue('E' . $row, $total_sum);
        $sheet->getStyle('E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
        $sheet->getStyle('A' . $row . ':E' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
    }

    // Border Ekleme
    $sheet->getStyle('A' . ($tableStartRow - 1) . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $filename = "icra_dosya_kesintileri_" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $icra_file->dosya_no) . ".xlsx";
    ActivityLogModel::log('icra', 'excel_export', "Tek icra dosyası detay excel raporu indirildi. Dosya No: {$icra_file->dosya_no}");

} else {
    // 2. TÜM İCRA DOSYALARI LİSTESİ EXCEL'İ
    $sheet->setTitle('İcra Dosyaları Listesi');

    // Başlık
    $sheet->setCellValue('A1', 'TÜM PERSONEL İCRA DOSYALARI LİSTESİ');
    $sheet->mergeCells('A1:L1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E293B'));
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

    $headers = [
        'Sıra', 'Personel Adı Soyadı', 'TC Kimlik No', 'Sicil No',
        'İcra Sırası', 'İcra Dairesi', 'Dosya No', 'Alacaklı / Avukat',
        'Toplam Borç', 'Kesinti Yöntemi', 'Yapılan Kesinti', 'Kalan Borç', 'Durum'
    ];
    $cols = range('A', 'M');

    $row = 3;
    foreach ($headers as $idx => $text) {
        $cell = $cols[$idx] . $row;
        $sheet->setCellValue($cell, $text);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF206BC4');
        $sheet->getStyle($cell)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
    }

    $status_filter = $_GET['status_filter'] ?? [];
    if (is_string($status_filter)) {
        $status_filter = explode(',', $status_filter);
    }

    $files = $PersonIcra->getFirmIcraFiles($firm_id, $status_filter);

    $row++;
    $i = 1;
    $sum_toplam = 0.0;
    $sum_kesilen = 0.0;
    $sum_kalan = 0.0;

    foreach ($files as $f) {
        $toplam = (float)$f->toplam_borc;
        $kesilen = (float)$f->yapilan_kesinti;
        $kalan = (float)$f->kalan_borc;

        $sum_toplam += $toplam;
        $sum_kesilen += $kesilen;
        $sum_kalan += $kalan;

        $decryptedKimlik = Security::safeDecrypt($f->kimlik_no ?? '');

        $kesintiSekli = ($f->kesinti_yontemi === 'sabit') 
            ? ($f->kesinti_tutari ? Helper::formattedMoney($f->kesinti_tutari) . ' (Sabit)' : 'Sabit')
            : (($f->kesinti_orani ?: '%25') . ' (Oran)');

        $sheet->setCellValue('A' . $row, $i++);
        $sheet->setCellValue('B' . $row, $f->full_name);
        $sheet->setCellValue('C' . $row, $decryptedKimlik ?: '-');
        $sheet->setCellValue('D' . $row, $f->sigorta_no ?: '-');
        $sheet->setCellValue('E' . $row, $f->icra_sirasi . '. Sıra');
        $sheet->setCellValue('F' . $row, $f->icra_dairesi);
        $sheet->setCellValue('G' . $row, $f->dosya_no);
        $sheet->setCellValue('H' . $row, $f->alacakli);
        $sheet->setCellValue('I' . $row, $toplam);
        $sheet->setCellValue('J' . $row, $kesintiSekli);
        $sheet->setCellValue('K' . $row, $kesilen);
        $sheet->setCellValue('L' . $row, $kalan);
        $sheet->setCellValue('M' . $row, $f->durum);

        $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
        $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
        $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
        $row++;
    }

    // Toplam Satırı
    $sheet->setCellValue('A' . $row, 'GENEL TOPLAM');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $sheet->setCellValue('I' . $row, $sum_toplam);
    $sheet->setCellValue('K' . $row, $sum_kesilen);
    $sheet->setCellValue('L' . $row, $sum_kalan);

    $sheet->getStyle('I' . $row)->getFont()->setBold(true)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
    $sheet->getStyle('K' . $row)->getFont()->setBold(true)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
    $sheet->getStyle('L' . $row)->getFont()->setBold(true)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
    $sheet->getStyle('A' . $row . ':M' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

    $sheet->getStyle('A3:M' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $filename = "icra_dosyalari_listesi_" . date('d_m_Y') . ".xlsx";
    ActivityLogModel::log('icra', 'excel_export', "Tüm icra dosyaları listesi excel olarak indirildi.");
}

// Otomatik Sütun Genişliği
foreach (range('A', 'M') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Excel Çıktısı
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
