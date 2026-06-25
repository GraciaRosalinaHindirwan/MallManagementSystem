<?php

require_once __DIR__ . '/UserRepositoryInterface.php';
require_once __DIR__ . '/UserRepository.php';

class UserRepositoryFactory
{
    private static ?UserRepositoryInterface $instance = null;

    private function __construct() {}

    public static function getInstance(): UserRepositoryInterface
    {
        if (self::$instance === null) {

            self::$instance = new UserRepository();
        }

        return self::$instance;
    }
}