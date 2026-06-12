<?php

require_once __DIR__ . "/../../domain/Contract.php";

interface IContractQuery
{
    public function get_by_id(int $id): Contract;

    public function get_by_user_id(int $user_id): Contract;
}
