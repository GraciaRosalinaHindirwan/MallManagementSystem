<?php

require_once __DIR__ . "/../../IUserQuery.php";
require_once __DIR__ . "/../../domain/User.php";

class InMemoryUserQuery implements IUserQuery
{
    private array $users;

    public function __construct(array $users)
    {
        $this->users = $users;
    }

    public function get_by_id(int $id): User
    {
        $filtered = array_filter($this->users, function (User $user) use ($id) {
            return $user->id == $id;
        });

        $values = array_values($filtered);

        return array_shift($values);
    }

    public function get_by_username(string $username)
    {
        $filtered = array_values(
            array_filter(
                $this->users,
                function (User $user) use ($username) {
                    return $user->username == $username;
                }
            )
        );

        return array_shift($filtered);
    }
}
