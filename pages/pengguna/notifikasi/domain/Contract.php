<?php

enum ContractStatus {}

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
}
