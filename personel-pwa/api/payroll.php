<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../Database/require.php';
require_once __DIR__ . '/../../Model/Persons.php';
require_once __DIR__ . '/../../Model/Bordro.php';
require_once __DIR__ . '/../../Model/ActivityLogModel.php';

if (!isset($_SESSION['personel_id'], $_SESSION['firm_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Oturum süreniz dolmuş.']);
    exit;
}

$person_id = (int)$_SESSION['personel_id'];
$firm_id = (int)$_SESSION['firm_id'];
$action = $_GET['action'] ?? 'list';
$persons = new Persons();
$bordro = new Bordro();
$person = $persons->find($person_id);

if (!$person || (int)$person->firm_id !== $firm_id) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Personel doğrulanamadı.']);
    exit;
}

$month_names = [1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

try {
    if ($action === 'list') {
        $periods = [];
        foreach ($bordro->getVisiblePayrollPeriodsForPerson($firm_id, $person_id) as $period) {
            $summary = $bordro->getPersonPayrollSummary($person_id, (int)$period->ay, (int)$period->yil);
            $periods[] = [
                'month' => (int)$period->ay,
                'year' => (int)$period->yil,
                'label' => $month_names[(int)$period->ay] . ' ' . (int)$period->yil,
                'gross' => $summary->gross,
                'deductions' => $summary->deductions,
                'net' => $summary->net,
                'work_days' => $summary->work_days,
                'total_hours' => $summary->total_hours
            ];
        }

        echo json_encode(['status' => 'success', 'periods' => $periods], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'detail') {
        $month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);

        if (!$month || $month < 1 || $month > 12 || !$year || $year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Geçersiz bordro dönemi.');
        }

        if ($bordro->getPeriodVisibility($firm_id, $year, $month) !== 1) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Bu dönem henüz kesinleşmediği için görüntülenemez.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $has_payroll = false;
        foreach ($bordro->getVisiblePayrollPeriodsForPerson($firm_id, $person_id) as $period) {
            if ((int)$period->ay === $month && (int)$period->yil === $year) {
                $has_payroll = true;
                break;
            }
        }

        if (!$has_payroll) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Bu döneme ait bordro kaydınız bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $summary = $bordro->getPersonPayrollSummary($person_id, $month, $year);
        $incomes = array_map(static function ($item) {
            return ['name' => $item->turu ?: 'Gelir', 'amount' => (float)$item->tutar];
        }, $bordro->getPersonIncome($person_id, $month, $year));
        $expenses = array_map(static function ($item) {
            return ['name' => $item->turu ?: 'Kesinti', 'amount' => (float)$item->tutar];
        }, $bordro->getPersonExpense($person_id, $month, $year));

        ActivityLogModel::log('payroll', 'personnel_view', "Personel {$person_id}, {$year}-{$month} dönemi kesinleşmiş bordrosunu görüntüledi.");

        echo json_encode([
            'status' => 'success',
            'payroll' => [
                'month' => $month,
                'year' => $year,
                'label' => $month_names[$month] . ' ' . $year,
                'gross' => $summary->gross,
                'deductions' => $summary->deductions,
                'net' => $summary->net,
                'work_days' => $summary->work_days,
                'total_hours' => $summary->total_hours,
                'incomes' => $incomes,
                'expenses' => $expenses
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem.']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'Bordro bilgileri alınamadı.'], JSON_UNESCAPED_UNICODE);
}
