<?php

interface IApprovalRequestQuery
{
    /** @return ApprovalRequest[]*/
    function get_all();

    /** @return ApprovalRequest[]*/
    function get_pending();
}
