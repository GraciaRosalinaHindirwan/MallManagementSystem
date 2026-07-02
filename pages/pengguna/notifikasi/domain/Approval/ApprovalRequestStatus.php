<?php
enum ApprovalRequestStatus
{
    case Draft;
    case Pending;
    case Approved;
    case Rejected;

    public static function from_string(string $from)
    {
        return match ($from) {
            "Draft" => self::Draft,
            "Pending" => self::Pending,
            "Approved" => self::Approved,
            "Rejected" => self::Rejected,
        };
    }
}
