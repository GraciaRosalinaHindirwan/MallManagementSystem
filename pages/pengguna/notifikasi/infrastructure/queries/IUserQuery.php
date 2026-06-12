<?php

require_once __DIR__ . "/../../domain/User.php";

interface IUserQuery
{
    public function get_by_id(int $id): User;

    public function get_by_username(string $username): User;
}
