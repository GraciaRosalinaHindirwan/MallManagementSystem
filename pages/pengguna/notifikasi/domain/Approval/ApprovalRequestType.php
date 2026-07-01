<?php

enum ApprovalRequestType
{
    case Contract;
    case Renovation;
    case Purchase;
    case Event;
    case Maintenance;

    public static function from_string(string $from)
    {
        return match ($from) {
            "Contract" => self::Contract,
            "Renovation" => self::Renovation,
            "Purchase" => self::Purchase,
            "Event" => self::Event,
            "Maintenance" => self::Maintenance,
            default => throw new Exception("string isn't 'capitalized or invalid request type")
        };
    }
}
