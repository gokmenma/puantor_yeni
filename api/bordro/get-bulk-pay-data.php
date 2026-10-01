<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Bordro.php';
require_once ROOT . '/Model/Projects.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

header('Content-Type: application/json; charset=utf-8');

try {
    $firmId = (int) ($_SESSION['firm_id'] ?? 0);
    $user = $_SESSION['user'] ?? null;
    if ($firmId <= 0 || !$user) {
        throw new RuntimeException('Yetkisiz erişim.');
    }

    $Auths = new Auths();
    $Auths->checkFirmReturn();
    $Auths->hasPermissionReturn('make_staff_payment');

    $personObj = new Persons();
    $bordro = new Bordro();
    $projects = new Projects();

    $isOverall = !empty($_POST['is_overall']) || (isset($_POST['mode']) && $_POST['mode'] === 'overall');
    $colors = ['primary', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan'];
    $modalPersonList = [];

    if ($isOverall) {
        // Dönemden bağımsız: Tüm personellerin toplam hakediş, toplam ödeme ve güncel toplam bakiyeleri
        $persons = $personObj->getPersonIdByFirm($firmId);
        $personIds = array_map(static function ($item) {
            return (int) $item->id;
        }, $persons);

        $personDetails = $personObj->getPersonsByIds($personIds);
        $personDetailsMap = [];
        foreach ($personDetails as $personDetail) {
            $personDetailsMap[(int) $personDetail->id] = $personDetail;
        }

        $overallSummaries = $bordro->getOverallPersonsSummary($personIds);

        foreach ($persons as $item) {
            $person = $personDetailsMap[(int) $item->id] ?? null;
            if (!$person) {
                continue;
            }

            $summary = $overallSummaries[(int) $person->id] ?? (object) [
                'total_income' => 0.0,
                'total_expense' => 0.0,
                'balance' => 0.0
            ];

            $gelir = (float) ($summary->total_income ?? 0);
            $odeme = (float) ($summary->total_expense ?? 0);
            $kalan = max(0, (float) ($summary->balance ?? 0));
            $hasBalance = ($kalan > 0.005);

            // Pasif personelse ve alacağı kalmamışsa listeye dahil etme
            $isActive = empty($person->job_end_date);
            if (!$isActive && !$hasBalance) {
                continue;
            }

            // Baş harfler
            $words = preg_split('/\s+/', trim((string) ($person->full_name ?? '')));
            $initials = '';
            foreach ($words as $w) {
                if (!empty($w)) {
                    $initials .= mb_substr($w, 0, 1, 'UTF-8');
                }
            }
            $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');

            $color = $colors[((int) $person->id) % count($colors)];
            $fullName = (string) ($person->full_name ?? '');
            $tcNo = (string) ($person->tc_no ?? '');
            $jobName = (string) ($person->job_name ?? ($person->duty_name ?? ($person->job ?? '')));
            $searchData = mb_strtolower($fullName . ' ' . $tcNo . ' ' . $jobName, 'UTF-8');

            $modalPersonList[] = [
                'id' => (int) $person->id,
                'full_name' => $fullName,
                'tc_no' => $tcNo,
                'job_name' => $jobName,
                'initials' => $initials ?: 'P',
                'color' => $color,
                'gelir' => $gelir,
                'formatted_gelir' => Helper::formattedMoney($gelir),
                'odenen' => $odeme,
                'formatted_odenen' => Helper::formattedMoney($odeme),
                'kalan' => $kalan,
                'formatted_kalan' => Helper::formattedMoney($kalan),
                'has_balance' => $hasBalance,
                'search_data' => $searchData
            ];
        }

        $periodTitle = 'Tüm Dönemler';
        $month = 0;
        $year = 0;

    } else {
        // Bordro Sayfası: Belirli bir aya ait dönem hakediş ve ödemeleri
        $month = (int) ($_POST['month'] ?? ($_SESSION['period_month'] ?? date('m')));
        $year = (int) ($_POST['year'] ?? ($_SESSION['period_year'] ?? date('Y')));
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $teamId = trim((string) ($_POST['team_id'] ?? ''));

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Geçersiz dönem bilgisi.');
        }

        $firstDay = Date::firstDay($month, $year);
        $last_day = Date::Ymd(Date::lastDay($month, $year));
        $lastDay = Date::lastDay($month, $year);

        if ($projectId > 0) {
            $persons = $projects->getPersonIdByFromProjectCurrentMonth($projectId, $firstDay, $last_day, 0, $teamId, true);
        } else {
            $persons = $personObj->getPersonIdByFirmCurrentMonth($firmId, $firstDay, $last_day, false, $teamId);
        }

        $personIds = array_map(static function ($item) {
            return (int) $item->id;
        }, $persons);

        $personDetails = $personObj->getPersonsByIds($personIds);
        $personDetailsMap = [];
        foreach ($personDetails as $personDetail) {
            $personDetailsMap[(int) $personDetail->id] = $personDetail;
        }

        $salaryAndWageCutMap = $bordro->getPersonsSalaryAndWageCut($personIds, $firstDay, $lastDay);
        $icraAmountMap = $bordro->getIcraAmounts($personIds, $month, $year);

        foreach ($persons as $item) {
            $person = $personDetailsMap[(int) $item->id] ?? null;
            if (!$person) {
                continue;
            }

            if (!empty($person->job_end_date)) {
                $job_end_date_ymd = Date::Ymd($person->job_end_date);
                if ($job_end_date_ymd < $firstDay) {
                    continue;
                }
            }

            $res = $salaryAndWageCutMap[(int) $person->id] ?? (object) ['gelir' => null, 'odeme' => 0];
            $p_icra = !empty($person->icra_kesintisi_aktif) ? ($icraAmountMap[(int) $person->id] ?? 0) : 0;

            $gelir = (float) ($res->gelir ?? 0);
            $odeme = (float) ($res->odeme ?? 0);
            $kalan = max(0, $gelir - $odeme);
            $hasBalance = ($kalan > 0.005);

            // Baş harfler
            $words = preg_split('/\s+/', trim((string) ($person->full_name ?? '')));
            $initials = '';
            foreach ($words as $w) {
                if (!empty($w)) {
                    $initials .= mb_substr($w, 0, 1, 'UTF-8');
                }
            }
            $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');

            $color = $colors[((int) $person->id) % count($colors)];
            $fullName = (string) ($person->full_name ?? '');
            $tcNo = (string) ($person->tc_no ?? '');
            $jobName = (string) ($person->job_name ?? ($person->duty_name ?? ($person->job ?? '')));
            $searchData = mb_strtolower($fullName . ' ' . $tcNo . ' ' . $jobName, 'UTF-8');

            $modalPersonList[] = [
                'id' => (int) $person->id,
                'full_name' => $fullName,
                'tc_no' => $tcNo,
                'job_name' => $jobName,
                'initials' => $initials ?: 'P',
                'color' => $color,
                'gelir' => $gelir,
                'formatted_gelir' => Helper::formattedMoney($gelir),
                'odenen' => $odeme,
                'formatted_odenen' => Helper::formattedMoney($odeme),
                'kalan' => $kalan,
                'formatted_kalan' => Helper::formattedMoney($kalan),
                'has_balance' => $hasBalance,
                'search_data' => $searchData
            ];
        }

        $periodTitle = Date::monthName($month) . ' ' . $year;
    }

    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode([
        'status' => 'success',
        'is_overall' => $isOverall,
        'period_title' => $periodTitle,
        'month' => $month,
        'year' => $year,
        'persons' => $modalPersonList
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
