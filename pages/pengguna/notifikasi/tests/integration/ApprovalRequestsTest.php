<?php

require_once __DIR__ . "/../../infrastructure/queries/mysql/MysqlApprovalRequestQuery.php";

use PHPUnit\Framework\TestCase;

class ApprovalRequestsTest extends TestCase
{
    private mysqli $db;

    #[Override]
    protected function setUp(): void
    {
        $this->db = new mysqli("localhost", "root", "", "mall_erp");
        $this->db->begin_transaction();

        Prepopulate::prepopulate_data($this->db, __DIR__ . "/db_populate/populate_approval_request.sql");
    }

    function testQueryingApprovalTableWithFiveMember_ShouldReturnFiveRows()
    {
        $approval_request = new MysqlApprovalRequestQuery($this->db);
        $approvals = $approval_request->get_all();

        $this->assertCount(5, $approvals);
    }

    function testQueryingPendingRequest_ShouldGiveTwoApprovalRequests()
    {
        $approval_request = new MysqlApprovalRequestQuery($this->db);
        $pending = $approval_request->get_pending();

        $this->assertCount(2, $pending);
    }

    #[Override]
    function tearDown(): void
    {
        $this->db->rollback();
        $this->db->close();
    }
}
