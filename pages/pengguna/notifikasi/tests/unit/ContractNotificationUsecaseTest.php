<?php

require_once __DIR__ . "/../infrastructure/queries/inmemory/InMemoryUserQuery.php";
require_once __DIR__ . "/../infrastructure/notifier/WebNotifier.php";
require_once __DIR__ . "/../infrastructure/queries/inmemory/InMemoryContractQuery.php";
require_once __DIR__ . "/../infrastructure/writer/inmemory/InMemoryNotificationWriter.php";
require_once __DIR__ . "/../infrastructure/queries/inmemory/InMemoryNotificationQuery.php";
require_once __DIR__ . "/../infrastructure/writer/inmemory/InMemoryNotificationLogWriter.php";
require_once __DIR__ . "/../usecase/ReceiveContractNotificationUseCase.php";


use PHPUnit\Framework\TestCase;

class ContractNotificationUseCaseTest extends TestCase
{
    private ReceiveContractNotificationUseCase $_usecase;
    private array $_notifications = [];
    private array $_notification_logs = [];
    private InMemoryNotificationWriter $_notification_writer;
    private InMemoryNotificationQuery $_notification_query;
    private InMemoryNotificationLogWriter $_notification_log_writer;
    private InMemoryUserQuery $_user_query;
    private InMemoryContractQuery $_contracts;
    private WebNotifier $_notifier;

    #[Override]
    public function setUp(): void
    {
        $this->_notification_writer = new InMemoryNotificationWriter($this->_notifications);
        $this->_notification_query = new InMemoryNotificationQuery($this->_notifications);

        $this->_notifier = new WebNotifier($this->_notification_writer, $this->_notification_query);

        $this->_user_query = new InMemoryUserQuery([
            User::create_default(1, "user1", "user1@gmail.com"),
            User::create_default(2, "user2", "user2@gmail.com"),
            User::create_default(3, "user3", "user3@gmail.com"),
        ]);
        $this->_contracts = new InMemoryContractQuery([
            Contract::create_default(1, 1, 1, 1, new DateTime("+1 day")),
            Contract::create_default(2, 2, 2, 2, new DateTime("+6 days")),
            Contract::create_default(3, 3, 3, 3, new DateTime("+30 days")),
        ]);

        $this->_notification_log_writer = new InMemoryNotificationLogWriter($this->_notification_logs);

        $this->_usecase = new ReceiveContractNotificationUseCase(
            $this->_notifier,
            $this->_user_query,
            $this->_contracts,
            $this->_notification_log_writer
        );
    }

    public function testNotificationShouldNotBeEmpty_AfterCallingUsecase()
    {
        $user = $this->_user_query->get_by_id(1);
        $this->_usecase->execute($user);

        $this->assertNotEmpty($this->_notifications);
        $this->assertNotEmpty($this->_notification_logs);
    }

    public function testShouldNotSendNotification_IfTheContractIsNotExpiringSoon()
    {
        $user = $this->_user_query->get_by_id(1);
        $this->_usecase->execute($user);

        $this->assertCount(2, $this->_notifications);
        $this->assertCount(2, $this->_notification_logs);
    }
}
