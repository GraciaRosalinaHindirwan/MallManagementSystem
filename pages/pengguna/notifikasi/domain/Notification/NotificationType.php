<?php
enum NotificationType
{
    case contract_expiry;
    case payment_due;
    case approval_request;
    case approval_result;
}
