<?php

require_once __DIR__ . "/ApprovalRequestStatus.php";
require_once __DIR__ . "/ApprovalRequestType.php";


class ApprovalRequest
{
    public int $approval_id;
    public string $approval_number;
    public ApprovalRequestType $request_type;
    public string $title;
    public string $description;
    public ApprovalRequestStatus $status;
    public int $current_level;
    public string $submitted_by;
    public DateTime $submitted_at;
    public ?string $approved_by;
    public DateTime $approved_at;
    public ?string $reject_reason;
    public DateTime $created_at;
    public DateTime $updated_at;

    public function __construct(
        int $approval_id,
        string $approval_number,
        ApprovalRequestType $request_type,
        string $title,
        string $description,
        ApprovalRequestStatus $status,
        int $current_level,
        string $submitted_by,
        DateTime $submitted_at,
        ?string $approved_by,
        DateTime $approved_at,
        ?string $reject_reason,
        DateTime $created_at,
        DateTime $updated_at,

    ) {
        $this->approval_id = $approval_id;
        $this->approval_number = $approval_number;
        $this->request_type = $request_type;
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->current_level = $current_level;
        $this->submitted_by = $submitted_by;
        $this->submitted_at = $submitted_at;
        $this->approved_by = $approved_by;
        $this->approved_at = $approved_at;
        $this->reject_reason = $reject_reason;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function create_default(int $approval_id, ApprovalRequestStatus $status)
    {
        return new ApprovalRequest(
            approval_id: $approval_id,
            approval_number: "",
            request_type: ApprovalRequestType::Contract,
            title: "",
            description: "",
            status: $status,
            current_level: 1,
            submitted_by: "",
            submitted_at: new DateTime(),
            approved_by: "",
            approved_at: new DateTime(),
            reject_reason: "",
            created_at: new DateTime(),
            updated_at: new DateTime()
        );
    }
}
