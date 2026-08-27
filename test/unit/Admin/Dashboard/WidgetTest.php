<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\Dashboard;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Dashboard\Widget;

#[CoversClass(Widget::class)]
final class WidgetTest extends TestCase
{
    #[Test]
    public function exposesDashboardMetadataAndCounts(): void
    {
        $widget = new Widget('admin.acl', 1, 2, 3, 4, 5);

        self::assertSame('ACL Management', $widget->title);
        self::assertSame('read', $widget->privilege);
        self::assertSame('acl::admin-widget', $widget->template);
        self::assertSame(10, $widget->order);
        self::assertSame('admin.acl', $widget->getResourceId());
        self::assertSame(1, $widget->roleCount);
        self::assertSame(2, $widget->resourceCount);
        self::assertSame(3, $widget->ruleCount);
        self::assertSame(4, $widget->assertionCount);
        self::assertSame(5, $widget->aclVersion);
    }
}
