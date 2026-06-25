<?php

require_once __DIR__ . "/../../../domain/Approval/ApprovalRequest.php";


class MysqlApprovalRequestQuery
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    private function result_to_approval_requests(array $result)
    {
        $approval_requests = [];

        foreach ($result as $approval) {
            array_push(
                $approval_requests,
                new ApprovalRequest(
                    approval_id: $approval["approval_id"],
                    request_number: $approval["request_number"],
                    request_type: ApprovalRequestType::from_string($approval["request_type"]),
                    title: $approval["title"],
                    description: $approval["description"],
                    status: ApprovalRequestStatus::from_string($approval["status"]),
                    current_level: $approval["current_level"],
                    submitted_by: $approval["submitted_by"],
                    submitted_at: new DateTime($approval["submitted_at"]),
                    approved_by: $approval["approved_by"],
                    approved_at: new DateTime($approval["approved_at"]),
                    reject_reason: $approval["reject_reason"],
                    created_at: new DateTime($approval["created_at"]),
                    updated_at: new DateTime($approval["updated_at"]),
                )
            );
        }
        return $approval_requests;
    }

    /** @return ApprovalRequest[] */
    public function get_all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM 08_approval_requests;");
        $stmt->execute();

        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if ($result == [] || $result == null) return [];

        return $this->result_to_approval_requests($result);
    }

    public function get_pending(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM 08_approval_requests WHERE status = 'pending'");
        $stmt->execute();

        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if ($result == [] || $result == null) return [];

        return $this->result_to_approval_requests($result);
    }
}
