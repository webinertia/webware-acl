<?php

declare(strict_types=1);

namespace Webware\Acl\Entity;

use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Override;
use PhpDb\ResultSet\RowPrototypeInterface;
use Webware\Acl\RuleType;

use function json_decode;

final class Rule implements RowPrototypeInterface, ResourceInterface, RoleInterface
{
    public function __construct(
        public private(set) int|string|null $id = null,
        public private(set) RuleType $type = RuleType::Allow {
            set(string|RuleType $value) {
                $this->type = $this->resolveType($value);
            }
        },
        public private(set) ?string $roleId = null,
        public private(set) ?string $resourceId = null,
        public private(set) ?array $assertions = null {
            set(?array $value) {
                $this->assertions = null === $value ? null : json_decode(json_encode($value), true);
            }
        },
        public private(set) ?string $parentResourceId = null,
    ) {}

    #[Override]
    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    #[Override]
    public function getRoleId(): ?string
    {
        return $this->roleId;
    }

    #[Override]
    public function populate(array $data): RowPrototypeInterface
    {
        return new static(...$data);
    }

    #[Override]
    public function toArray(): array
    {
        return (array) $this;
    }

    public function withRowData(array $withRowData): static
    {
        return $this->populate(data: $withRowData);
    }

    private function resolveType(string|RuleType $type): RuleType
    {
        if ($type instanceof RuleType) {
            return $type;
        }

        return RuleType::from($type);
    }
}
