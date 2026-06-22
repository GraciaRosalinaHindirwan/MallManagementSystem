<?php

require_once __DIR__ . "/../../infrastructure/queries/mysql/MysqlContractQuery.php";

use PHPUnit\Framework\TestCase;

class ContractTest extends TestCase
{
    private mysqli $db;

    #[Override]
    function setUp(): void
    {
        $this->db = new mysqli("localhost", "root", "", "mall_management");
        $this->db->begin_transaction();
    }

    function testQueryingWithAnIdOfTwo_ShouldGiveTheAppropriateContractNumber()
    {
        $contract_query = new MysqlContractQuery($this->db);

        $contract = $contract_query->get_by_id(2);

        $this->assertEquals("CONT-2026-002", $contract->contract_number);
    }

    function testQueryingAll_ShouldHaveTheAppropriateContractNumber()
    {
        $contract_query = new MysqlContractQuery($this->db);

        $contracts = $contract_query->get_all();

        $this->assertEquals("CONT-2026-001", $contracts[0]->contract_number);
        $this->assertEquals("CONT-2026-002", $contracts[1]->contract_number);
        $this->assertEquals("CONT-2026-003", $contracts[2]->contract_number);
    }


    #[Override]
    function tearDown(): void
    {
        $this->db->rollback();
        $this->db->close();
    }
}
