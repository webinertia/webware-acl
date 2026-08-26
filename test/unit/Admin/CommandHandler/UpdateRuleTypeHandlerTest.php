<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\Acl\Admin\Command\UpdateRuleTypeCommand;
use Webware\Acl\Admin\CommandHandler\UpdateRuleTypeHandler;
use Webware\Acl\Repository\RoleRepository;
use Webware\Acl\Repository\RuleRepository;
use Webware\Acl\RuleType;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMock;

#[CoversClass(UpdateRuleTypeHandler::class)]
final class UpdateRuleTypeHandlerTest extends TestCase
{
    use PhpDbAdapterMock;

    #[Test]
    public function handleReturnsFailureWhenRepositoryThrows(): void
    {
        $handler = new UpdateRuleTypeHandler(
            new RuleRepository($this->createAdapter([], [], new RuntimeException('boom'))),
            new RoleRepository($this->createAdapter([])),
        );
        $result = $handler->handle(new UpdateRuleTypeCommand('Admin', 'dashboard', RuleType::Deny));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function handleReturnsFailureWhenUpdateAffectsNoRows(): void
    {
        $handler = new UpdateRuleTypeHandler(
            new RuleRepository($this->createAdapter([[]], [0])),
            new RoleRepository($this->createAdapter([])),
        );
        $result = $handler->handle(new UpdateRuleTypeCommand('Admin', 'dashboard', RuleType::Deny));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function handleSkipsChildrenThatAlreadyHaveRules(): void
    {
        $handler = new UpdateRuleTypeHandler(
            new RuleRepository($this->createAdapter([
                [],
                [[
                    'type'       => 'Allow',
                    'roleId'     => 'Editor',
                    'resourceId' => 'dashboard',
                    'assertions' => '["Ownership"]',
                ]],
            ])),
            new RoleRepository($this->createAdapter([
                [['roleId' => 'Editor']],
            ])),
        );
        $result = $handler->handle(new UpdateRuleTypeCommand('Admin', 'dashboard', RuleType::Deny));

        self::assertSame(MessageStatus::Success, $result->getStatus());
    }

    #[Test]
    public function handleUpdatesTypeAndCascadesToChildrenWithoutRules(): void
    {
        $handler = new UpdateRuleTypeHandler(
            new RuleRepository($this->createAdapter([
                [],
                [],
                [],
                [],
            ])),
            new RoleRepository($this->createAdapter([
                [['roleId' => 'Editor']],
            ])),
        );
        $result = $handler->handle(new UpdateRuleTypeCommand('Admin', 'dashboard', RuleType::Deny));

        self::assertSame(MessageStatus::Success, $result->getStatus());
    }
}
