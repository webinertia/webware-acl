<?php

declare(strict_types=1);

namespace Webware\Acl\Entity;

use Laminas\Permissions\Acl\Role\RoleInterface;
use Webware\ResultSet\WithRowDataPrototypeInterface;

use function is_string;
use function json_decode;
use RuntimeException;

final class Role implements RoleInterface, WithRowDataPrototypeInterface
{
    public function __construct(
        public private(set) int|string|null $id = null,
        public private(set) int|string|null $roleId = null,
        /** @var RoleInterface[]|string[]|string|null The parent role identifiers. */
        public private(set) array|string|null $parentId = null {
            set(array|string|null $value) {
                if (null === $value) {
                    $this->parentId = null;
                } else {
                    if (is_string($value)) {
                        $value = json_decode($value, true);
                    }
                    $this->parentId = array_map(
                        static fn($id) => is_string($id) ? new self(roleId: $id) : $id,
                        $value,
                    );
                }
            }
        },
    ) {}

    public function exchangeArray(array $data): array
    {
        throw new RuntimeException('Not implemented');
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getParentId(): ?array
    {
        return $this->parentId;
    }

    public function getRoleId(): int|string|null
    {
        return $this->roleId;
    }

    public function toArray(): array
    {
        return (array) $this;
    }

    public function withRowData(array $withRowData): static
    {
        return new self(...$withRowData);
    }
}
