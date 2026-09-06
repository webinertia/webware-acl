<?php

declare(strict_types=1);

namespace WebwareTest\Acl\QueryHandler;

use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Query\FetchDistinctResourceIds;
use Webware\Acl\QueryHandler\FetchDistinctResourceIdsHandler;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(FetchDistinctResourceIdsHandler::class)]
final class FetchDistinctResourceIdsHandlerTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function handleReturnsDistinctResourceIds(): void
    {
        $handler = new FetchDistinctResourceIdsHandler($this->createRuleArrayGateway($this->createAdapter([
            [
                ['resourceId' => 'dashboard'],
                ['resourceId' => 'admin'],
            ],
        ])));
        $query  = new FetchDistinctResourceIds();
        $result = $handler->handle($query);

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame($query, $result->getQuery());

        $select = $this->preparedSqlObjects[0];
        self::assertInstanceOf(Select::class, $select);
        self::assertSame(['resourceId'], $select->getRawState('columns'));
        self::assertSame(Select::QUANTIFIER_DISTINCT, $select->getRawState('quantifier'));

        self::assertSame(['dashboard', 'admin'], $result->getResult());
    }
}
