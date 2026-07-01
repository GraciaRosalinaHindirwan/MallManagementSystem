<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../../domain/Feedback.php";

require_once __DIR__ . "/../../infrastructure/queries/mysql/MysqlFeedbackQuery.php";

class FeedbackTest extends TestCase
{
    private mysqli $db;

    #[Override]
    public function setUp(): void
    {
        $this->db = new mysqli("localhost", "root", "", "mall_management");
        $this->db->begin_transaction();

        Prepopulate::prepopulate_data($this->db, __DIR__ . "/db_populate/populate_feedback.sql");
    }

    #[Override]
    public function tearDown(): void
    {
        $this->db->rollback();
        $this->db->close();
    }

    public function testGetAll()
    {
        $feedback_query = new MysqlFeedbackQuery($this->db);

        $feedbacks = $feedback_query->get_all();

        $this->assertNotEmpty($feedbacks);
    }
}
