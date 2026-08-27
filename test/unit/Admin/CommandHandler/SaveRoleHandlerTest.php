<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\Admin\CommandHandler\SaveRoleHandler;
use Webware\Acl\Repository\RoleRepository;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(SaveRoleHandler::class)]
final class SaveRoleHandlerTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function handleReturnsFailureWhenRepositoryThrows(): void
    {
        $handler = new SaveRoleHandler(
            new RoleRepository($this->createAdapter([], [], new RuntimeException('boom'))),
        );
        $result = $handler->handle(new SaveRoleCommand(null, 'Editor', null));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
        self::assertInstanceOf(RuntimeException::class, $result->getResult());
    }

    #[Test]
    public function handleSavesRoleAndReturnsSuccess(): void
    {
        $handler = new SaveRoleHandler(new RoleRepository($this->createAdapter([[], []])));
        $result  = $handler->handle(new SaveRoleCommand(null, 'Editor', null));

        self::assertSame(MessageStatus::Success, $result->getStatus());
    }
}
