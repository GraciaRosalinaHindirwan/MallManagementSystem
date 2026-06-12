<?php
require_once __DIR__ . "/persistence/queries/InMemoryUserQuery.php";

// file ini bertujuan untuk menguji dan bereksperiment dengan modul

$userquery = new InMemoryUserQuery([
    new User(1, "bla1", "bla@mail.com"),
    new User(2, "bla2", "bla@mail.com"),
    new User(3, "bla3", "bla@mail.com"),
]);

$user_by_id = $userquery->get_by_id(2);

echo "user by id: \n";
var_dump($user_by_id);

$user_by_username = $userquery->get_by_username("bla2");

echo "user by username: \n";
var_dump($user_by_username);
