<?php

declare(strict_types=1);

namespace Webware\Acl\Assertion;

use Laminas\Permissions\Acl\Assertion\AssertionAggregate as BaseAggregate;
use Laminas\Permissions\Acl\Assertion\AssertionManager as BaseManager;
use Override;
use Webware\Acl\AssertionManager;

final class AssertionAggregate extends BaseAggregate
{
    #[Override]
    public function getAssertionManager(): ?AssertionManager
    {
        return $this->assertionManager;
    }

    #[Override]
    public function setAssertionManager(BaseManager|AssertionManager $manager): self
    {
        $this->assertionManager = $manager;

        return $this;
    }
}
