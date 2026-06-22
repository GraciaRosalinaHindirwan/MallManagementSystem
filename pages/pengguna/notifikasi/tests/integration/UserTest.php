<?php

require_once __DIR__ . "/../../infrastructure/queries/mysql/MysqlUserQuery.php";

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private mysqli $db;

    #[Override]
    function setUp(): void
    {
        $this->db = new mysqli("localhost", "root", "", "mall_management");
        $this->db->begin_transaction();
    }

    function testGetById()
    {
        $query = new MysqlsUserQuery($this->db);

        $user = $query->get_by_id(1);

        $this->assertEquals("superadmin", $user->username);
    }

    function testGetByUsername()
    {
        $query = new MysqlsUserQuery($this->db);

        $user = $query->get_by_username("superadmin");

        $this->assertEquals(1, $user->id);
    }

    function testGetAll()
    {
        $query = new MysqlsUserQuery($this->db);

        $users = $query->get_all();

        $this->assertNotEmpty($users);
    }

    #[Override]
    function tearDown(): void
    {
        $this->db->rollback();
        $this->db->close();
    }
}
