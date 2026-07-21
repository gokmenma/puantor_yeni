<?php

require_once __DIR__ . '/NationalHolidaysModel.php';
require_once __DIR__ . '/HolidayWorkPolicyModel.php';

class HolidayWorkService
{
    private $holidays;
    private $policies;

    public function __construct()
    {
        $this->holidays = new NationalHolidaysModel();
        $this->policies = new HolidayWorkPolicyModel();
    }

    public function calculate($firmId, $attendance, $dailyRate, $workHour)
    {
        if (!$attendance || empty($attendance->counts_as_work)) {
            return null;
        }

        $date = date('Y-m-d', strtotime($attendance->gun));
        $holiday = $this->holidays->findActiveByDate($date);
        if (!$holiday) {
            return null;
        }

        $policy = $this->policies->getPolicy($firmId, $holiday->holiday_type);
        if (empty($policy['is_active']) && array_key_exists('is_active', $policy)) {
            return null;
        }

        $additionalDays = (float) ($policy['additional_day_rate'] ?? 0);
        if ($additionalDays <= 0 || $dailyRate <= 0 || $workHour <= 0) {
            return null;
        }

        $holidayRatio = max(0, min(1, (float) ($holiday->day_ratio ?? 1)));
        $workedHours = max(0, (float) ($attendance->saat ?? 0));
        if (($policy['calculation_basis'] ?? 'pro_rata') === 'full_day') {
            $workedDayFraction = $workedHours > 0 ? $holidayRatio : 0;
        } else {
            $workedDayFraction = min($holidayRatio, $workedHours / (float) $workHour);
        }

        if ($workedDayFraction <= 0) {
            return null;
        }

        return (object) [
            'holiday_id' => (int) $holiday->id,
            'holiday_name' => $holiday->holiday_name,
            'holiday_type' => $holiday->holiday_type,
            'holiday_ratio' => $holidayRatio,
            'additional_day_rate' => $additionalDays,
            'calculation_basis' => $policy['calculation_basis'] ?? 'pro_rata',
            'worked_day_fraction' => $workedDayFraction,
            'amount' => round((float) $dailyRate * $workedDayFraction * $additionalDays, 2),
            'date' => $date,
        ];
    }
}

