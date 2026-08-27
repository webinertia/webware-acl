<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\Dashboard;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Dashboard\RegisterWidgetListener;
use Webware\Acl\Admin\Dashboard\Widget;
use Webware\Admin\Event\RegisterWidgetEvent;

use function iterator_to_array;

#[CoversClass(RegisterWidgetListener::class)]
final class RegisterWidgetListenerTest extends TestCase
{
    #[Test]
    public function registersAclWidgetOnEvent(): void
    {
        $listener = new RegisterWidgetListener('admin.acl', []);
        $event    = new RegisterWidgetEvent();

        $listener($event);

        $widgets = iterator_to_array($event->getWidgetContainer());
        self::assertCount(1, $widgets);
        self::assertInstanceOf(Widget::class, $widgets[0]);
        self::assertSame('admin.acl', $widgets[0]->getResourceId());
    }
}
