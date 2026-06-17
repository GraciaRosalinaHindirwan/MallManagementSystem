<?php

require_once __DIR__ . "/../usecase/ReceiveContractNotificationUseCase.php";
require_once __DIR__ . "/../infrastructure/notifier/ConsoleNotifier.php";
require_once __DIR__ . "/../infrastructure/queries/InMemoryUserQuery.php";
require_once __DIR__ . "/../infrastructure/queries/InMemoryContractQuery.php";
require_once __DIR__ . "/../infrastructure/writer/InMemoryNotificationLogWriter.php";

require_once __DIR__ . "/../domain/User.php";

class BackgroundTaskRunner
{
    private const TARGET_HOUR = 8;
    private const TARGET_MINUTE = 0;
    private const CHECK_INTERVAL_SECONDS = 60;

    private string $logFile;
    private bool $running = true;

    public function __construct(string $logFile = "php://stdout")
    {
        $this->logFile = $logFile;
    }

    public function run(): void
    {
        $this->log("Background task runner started. Will execute at " .
            self::TARGET_HOUR . ":" . str_pad((string) self::TARGET_MINUTE, 2, "0", STR_PAD_LEFT) . " daily.");

        $todayExecuted = false;
        $lastCheckedDate = "";

        while ($this->running) {
            $now = new DateTime();
            $today = $now->format("Y-m-d");
            $currentHour = (int) $now->format("H");
            $currentMinute = (int) $now->format("i");

            if ($today !== $lastCheckedDate) {
                $todayExecuted = false;
                $lastCheckedDate = $today;
            }

            if (
                !$todayExecuted &&
                $currentHour === self::TARGET_HOUR &&
                $currentMinute === self::TARGET_MINUTE
            ) {
                $this->log("Executing scheduled task at " . $now->format("Y-m-d H:i:s"));
                $this->executeTask();
                $todayExecuted = true;
                $this->log("Scheduled task completed.");
            }

            sleep(self::CHECK_INTERVAL_SECONDS);
        }
    }

    public function stop(): void
    {
        $this->running = false;
    }

    private function executeTask(): void
    {
        $user = new InMemoryUserQuery([
            new User(1, "user1", "user1@gmail.com"),
            new User(2, "user2", "user2@gmail.com"),
            new User(3, "user3", "user3@gmail.com"),
        ]);
        $notifier = new ConsoleNotifier();
        $contract = new InMemoryContractQuery([
            new Contract(1, 1, new DateTime()),
            new Contract(2, 2, new DateTime()),
            new Contract(3, 3, new DateTime()),
        ]);

        $log_writer = new InMemoryNotificationLogWriter([]);

        $usecase = new ReceiveContractNotificationUseCase($notifier, $user, $contract, $log_writer);
        $usecase->execute();
    }

    private function log(string $message): void
    {
        $timestamp = (new DateTime())->format("Y-m-d H:i:s");
        file_put_contents(
            $this->logFile,
            "[{$timestamp}] {$message}\n",
            FILE_APPEND
        );
    }
}

$runner = new BackgroundTaskRunner();
$runner->run();
