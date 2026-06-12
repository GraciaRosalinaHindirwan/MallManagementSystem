<?php

interface IUserQuery
{
    public function get_by_id(int $id);

    public function get_by_username(string $username);
}
