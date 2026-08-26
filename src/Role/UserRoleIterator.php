<?php

declare(strict_types=1);

namespace Webware\Acl\Role;

use Iterator;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Override;
use Webware\Core\UserInterface;

/**
 * @implements Iterator<int, UserInterface>
 */
final class UserRoleIterator implements Iterator
{
    /** @var array<RoleInterface|string> */
    private array $roles;

    private int $position = 0;

    public function __construct(
        private readonly UserInterface $user,
    ) {
        $this->roles = [...$user->getRoles()];
    }

    #[Override]
    public function current(): UserInterface
    {
        return new SingleRoleUserProxy($this->user, $this->roles[$this->position]);
    }

    #[Override]
    public function key(): int
    {
        return $this->position;
    }

    #[Override]
    public function next(): void
    {
        $this->position++;
    }

    #[Override]
    public function rewind(): void
    {
        $this->position = 0;
    }

    #[Override]
    public function valid(): bool
    {
        return isset($this->roles[$this->position]);
    }
}
