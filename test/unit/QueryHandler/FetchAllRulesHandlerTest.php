<?php

declare(strict_types=1);

namespace WebwareTest\Acl\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Query\FetchAllRules;
use Webware\Acl\QueryHandler\FetchAllRulesHandler;
use Webware\Acl\Repository\RuleRepository;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(FetchAllRulesHandler::class)]
final class FetchAllRulesHandlerTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function handleReturnsAllRules(): void
    {
        $handler = new FetchAllRulesHandler(new RuleRepository($this->createAdapter([
            [
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Admin',
                    'resourceId'       => 'dashboard',
                    'assertions'       => '["Ownership"]',
                    'parentResourceId' => null,
                ],
            ],
        ])));
        $query  = new FetchAllRules();
        $result = $handler->handle($query);

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame($query, $result->getQuery());
        self::assertSame(
            [
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Admin',
                    'resourceId'       => 'dashboard',
                    'assertions'       => ['Ownership'],
                    'parentResourceId' => null,
                ],
            ],
            $result->getResult(),
        );
    }
}
