<?php
enum ContractStatus
{
    case Draft;
    case WaitingApproval;
    case Active;
    case Ammended;
    case Expired;
    case Terminated;

    public static function from_string(string $from)
    {
        return match ($from) {
            "Draft" => Self::Draft,
            "WaitingApproval" => Self::WaitingApproval,
            "Active" => Self::Active,
            "Ammended" => Self::Ammended,
            "Expired" => Self::Expired,
            "Terminated" => Self::Terminated,
        };
    }

    public function to_string()
    {
        return match ($this) {
            self::Draft => "Draft",
            self::WaitingApproval => "WaitingApproval",
            self::Active => "Active",
            self::Ammended => "Ammended",
            self::Expired => "Expired",
            self::Terminated => "Terminated"
        };
    }
}
