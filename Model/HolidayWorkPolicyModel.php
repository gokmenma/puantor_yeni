<?php

require_once __DIR__ . '/BaseModel.php';

class HolidayWorkPolicyModel extends Model
{
    protected $table = 'holiday_work_policies';

    private const DEFAULTS = [
        'national' => ['additional_day_rate' => 1.0, 'calculation_basis' => 'pro_rata'],
        'religious' => ['additional_day_rate' => 1.0, 'calculation_basis' => 'pro_rata'],
        'other' => ['additional_day_rate' => 0.0, 'calculation_basis' => 'pro_rata'],
    ];

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getForFirm($firmId)
    {
        $policies = self::DEFAULTS;
        $sql = $this->db->prepare(
            "SELECT holiday_type, additional_day_rate, calculation_basis, is_active
             FROM {$this->table} WHERE firm_id = ?"
        );
        $sql->execute([(int) $firmId]);

        foreach ($sql->fetchAll(PDO::FETCH_OBJ) as $row) {
            $policies[$row->holiday_type] = [
                'additional_day_rate' => (float) $row->additional_day_rate,
                'calculation_basis' => $row->calculation_basis,
                'is_active' => (int) $row->is_active,
            ];
        }

        return $policies;
    }

    public function getPolicy($firmId, $holidayType)
    {
        $policies = $this->getForFirm($firmId);
        return $policies[$holidayType] ?? self::DEFAULTS['other'];
    }

    public function upsertForFirm($firmId, $holidayType, $additionalDayRate, $calculationBasis)
    {
        if (!array_key_exists($holidayType, self::DEFAULTS)) {
            throw new InvalidArgumentException('Geçersiz resmi tatil türü.');
        }
        if (!in_array($calculationBasis, ['pro_rata', 'full_day'], true)) {
            throw new InvalidArgumentException('Geçersiz tatil çalışma hesaplama yöntemi.');
        }

        $rate = max(0, min(10, (float) $additionalDayRate));
        $sql = $this->db->prepare(
            "INSERT INTO {$this->table}
                (firm_id, holiday_type, additional_day_rate, calculation_basis, is_active)
             VALUES (?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                additional_day_rate = VALUES(additional_day_rate),
                calculation_basis = VALUES(calculation_basis),
                is_active = 1"
        );
        return $sql->execute([(int) $firmId, $holidayType, $rate, $calculationBasis]);
    }
}

