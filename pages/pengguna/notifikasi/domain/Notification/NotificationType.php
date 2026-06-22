<?php
enum NotificationType
{
    case contract_expiry;
    case payment_due;
    case approval_request;
    case approval_result;

    public function to_string()
    {
        return match ($this) {
            self::contract_expiry => "contract_expiry",
            self::payment_due     => "payment_due",
            self::approval_request => "approval_request",
            self::approval_result  => "approval_result",
        };
    }

    public static function from_string(string $from)
    {
        return match ($from) {
            "contract_expiry" => self::contract_expiry,
            "payment_due"     => self::payment_due,
            "approval_request" => self::approval_request,
            "approval_result"  => self::approval_result,
        };
    }
}
