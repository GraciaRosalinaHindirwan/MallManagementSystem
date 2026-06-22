<?php

require_once __DIR__ . "/../../../domain/Contract.php";
require_once __DIR__ . "/../../../infrastructure/queries/IContractQuery.php";

class MysqlContactQuery implements IContractQuery
{
    private mysqli $db;
    const TABLE_NAME = "";

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function get_by_id(int $id): Contract
    {
        $stmt = $this->db->prepare("SELECT * FROM 02_contracts WHERE id_contract = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        $contract_status = ContractStatus::Draft;

        return new Contract(
            id: $result["id_contract"],
            contract_number: $result["contract_number"],
            tenant_id: $result["id_tenant"],
            unit_id: $result["id_unit"],
            start_date: new DateTime($result["start_date"]),
            end_date: new DateTime($result["end_date"]),
            contract_status: $contract_status,
            legal_document_url: $result["legal_document_url"],
        );
    }

    public function get_all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM 02_contracts");
        $stmt->execute();

        $result = $stmt->get_result()->fetch_all();

        $all = [];
        foreach ($result as $c) {
            array_push($all, new Contract(
                id: $c["id_contract"],
                contract_number: $c["contract_number"],
                tenant_id: $c["id_tenant"],
                unit_id: $c["id_unit"],
                start_date: new DateTime($c["start_date"]),
                end_date: new DateTime($c["end_date"]),
                contract_status: $c["contract_status"],
                legal_document_url: $c["legal_document_url"],
            ));
        }

        return $all;
    }
}
