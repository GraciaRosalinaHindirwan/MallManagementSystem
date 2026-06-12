<?php

require_once __DIR__ . "/IContractQuery.php";
require_once __DIR__ . "/../../domain/Contract.php";

class InMemoryContractQuery implements IContractQuery
{
    private array $contracts;

    function __construct(array $contracts)
    {
        $this->contracts = $contracts;
    }

    function get_by_id(int $contract_id): Contract
    {
        $filtered = array_values(array_filter(
            $this->contracts,
            function (Contract $c) use ($contract_id) {
                return $c->id == $contract_id;
            }
        ));

        return array_shift($filtered);
    }

    function get_by_user_id(int $user_id): Contract
    {
        $filtered = array_values(array_filter(
            $this->contracts,
            function (Contract $c) use ($user_id) {
                return $c->user_id == $user_id;
            }
        ));

        return array_shift($filtered);
    }
}
