<?php

require_once __DIR__ . "/../../infrastructure/writer/mysql/MysqlNotificationWriter.php";
require_once __DIR__ . "/../../infrastructure/queries/mysql/MysqlNotificationQuery.php";
require_once __DIR__ . "/../../domain/Notification/Notification.php";

use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    private mysqli $db;
    private MysqlNotificationWriter $_writer;
    private MysqlNotificationQuery $_query;

    #[Override]
    function setUp(): void
    {
        $this->db = new \mysqli("localhost", "root", "", "mall_management");
        $this->db->begin_transaction();

        $this->_writer = new MySqlNotificationWriter($this->db);
        $this->_query = new MysqlNotificationQuery($this->db);
    }

    function testInsertion()
    {
        $this->_writer->insert(new Notification(
            id: 1,
            content: new NotificationContent(
                subject: "subject",
                body: "this is an notification body",
                type: NotificationType::contract_expiry,
            ),
        ));

        $this->assertNotEmpty($this->_query->get_all());
    }

    function testShouldBeAbleToGetNotificationById()
    {
        $this->_writer->insert_with_id(new Notification(
            id: 1,
            content: new NotificationContent(
                subject: "subject",
                body: "this is an notification body",
                type: NotificationType::contract_expiry,
            ),
        ));

        $notification = $this->_query->get_by_id(1);

        $this->assertNotNull($notification);
        $this->assertEquals(1, $notification->id);
    }

    function tearDown(): void
    {
        $this->db->rollback();
        $this->db->close();
    }
}
