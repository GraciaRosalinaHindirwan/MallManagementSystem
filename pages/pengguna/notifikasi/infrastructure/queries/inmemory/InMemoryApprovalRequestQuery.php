<?php

require_once __DIR__ . "/../IApprovalRequestQuery.php";

class InMemoryApprovalRequestQuery implements IApprovalRequestQuery
{
    private array $requests;

    public function __construct(array $requests)
    {
        $this->requests = $requests;
    }

    /** @return ApprovalRequest[]*/
    public function get_all()
    {
        return $this->requests;
    }

    /** @return ApprovalRequest[]*/
    public function get_pending()
    {
        $pending = [];

        /** @var ApprovalRequest $request */
        foreach ($this->requests as $request) {
            if ($request->status != ApprovalRequestStatus::Pending)
                continue;

            array_push($pending, $request);
        }

        return $pending;
    }
}
