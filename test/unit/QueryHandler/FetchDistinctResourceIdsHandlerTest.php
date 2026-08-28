<?php

declare(strict_types=1);

namespace WebwareTest\Acl\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Query\FetchDistinctResourceIds;
use Webware\Acl\QueryHandler\FetchDistinctResourceIdsHandler;
use Webware\Acl\Repository\RuleRepository;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(FetchDistinctResourceIdsHandler::class)]
final class FetchDistinctResourceIdsHandlerTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function handleReturnsDistinctResourceIds(): void
    {
        $handler = new FetchDistinctResourceIdsHandler(new RuleRepository($this->createAdapter([
            [
                ['resourceId' => 'dashboard'],
                ['resourceId' => 'admin'],
            ],
        ])));
        $query  = new FetchDistinctResourceIds();
        $result = $handler->handle($query);

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame($query, $result->getQuery());
        self::assertSame(['dashboard', 'admin'], $result->getResult());
    }
}
