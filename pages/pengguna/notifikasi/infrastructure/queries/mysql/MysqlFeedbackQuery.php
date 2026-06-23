<?php

require_once __DIR__ . "/../../../domain/Feedback.php";

require_once __DIR__ . "/../IFeedbackQuery.php";

class MysqlFeedbackQuery implements IFeedbackQuery
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    #[Override]
    //** @return Feedback[] */
    public function get_all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM 05_cs_feedback");
        $stmt->execute();

        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if ($result == null) return [];
        if (count($result) <= 0) return [];

        $feedbacks = [];

        foreach ($result as $feedback) {
            array_push($feedbacks, new Feedback(
                $feedback["id"],
                $feedback["nama_pengunjung"],
                $feedback["rating"],
                $feedback["komentar"],
                $feedback["kategori"],
                new DateTime($feedback["created_at"]),
                new DateTime($feedback["updated_at"])
            ));
        }

        return $feedbacks;
    }
}
