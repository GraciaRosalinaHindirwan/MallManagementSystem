<?php

require_once __DIR__ . "/ContractStatus.php";

class Contract
{
    public int $id;
    public string $contract_number;
    public int $tenant_id;
    public int $unit_id;
    public DateTime $start_date;
    public DateTime $end_date;
    public ContractStatus $contract_status;
    public string $legal_document_url;

    public function __construct(
        int $id,
        string $contract_number,
        int $tenant_id,
        int $unit_id,
        DateTime $start_date,
        DateTime $end_date,
        ContractStatus $contract_status,
        string $legal_document_url,
    ) {
        $this->id = $id;
        $this->contract_number = $contract_number;
        $this->tenant_id = $tenant_id;
        $this->unit_id = $unit_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->contract_status = $contract_status;
        $this->legal_document_url = $legal_document_url;
    }

    public static function create_default(int $id, string $contract_number, int $tenant_id, int $unit_id, DateTime $expired_date): Contract
    {
        return new Contract(
            id: $id,
            contract_number: $contract_number,
            tenant_id: $tenant_id,
            unit_id: $unit_id,
            start_date: new DateTime(),
            end_date: $expired_date,
            contract_status: ContractStatus::Draft,
            legal_document_url: '',
        );
    }

    public function will_expire_in_days(int $days): bool
    {
        $current_time = new DateTime();
        $interval = $current_time->diff($this->end_date);
        return $interval->days <= $days && $interval->invert === 0;
    }
}
