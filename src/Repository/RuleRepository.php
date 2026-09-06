<?php

declare(strict_types=1);

namespace Webware\Acl\Repository;

use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use PhpDb\TableGateway\TableGateway;
use Webware\Acl\Entity\Rule;
use Webware\Acl\RuleType;

use function json_encode;

final class RuleRepository
{
    public function __construct(
        private readonly TableGateway $gateway,
    ) {}

    /**
     * Delete the rule for the given (roleId, resourceId) pair.
     */
    public function delete(string $roleId, string $resourceId): bool
    {
        return $this->gateway->delete(['roleId' => $roleId, 'resourceId' => $resourceId]) > 0;
    }

    /**
     * Returns true when a rule already exists for the given (roleId, resourceId) pair.
     *
     * @throws SqlException
     */
    public function hasRule(string $roleId, string $resourceId): bool
    {
        $select = $this->gateway->getSql()->select();
        $select->columns(['id'])
            ->where(['roleId' => $roleId, 'resourceId' => $resourceId])
            ->limit(1);

        return null !== $this->gateway->selectWith($select)->current();
    }

    /**
     * Insert or update a rule (upsert on the unique key roleId + resourceId).
     * Returns the rule ID on success, false on failure.
     *
     * $this->allow(Role, Resource, Privilege, Assertions) in the ACL corresponds to save(RuleType::Allow, Role, Resource, Assertions) here.;
     *
     * @param string[] $assertions
     * @throws SqlException
     */
    public function save(
        RuleType $type,
        string $roleId,
        string $resourceId,
        ?array $assertions,
        ?string $parentResourceId = null,
    ): int|false {
        if ([] === $assertions || [0 => ''] === $assertions) {
            $assertions = null;
        }

        $data = [
            'type'       => $type->value,
            'roleId'     => $roleId,
            'resourceId' => $resourceId,
        ];

        if (null !== $parentResourceId) {
            $data['parentResourceId'] = $parentResourceId;
        }

        if (null !== $assertions) {
            $data['assertions'] = json_encode($assertions);
        }

        $select = $this->gateway->getSql()->select();
        $select->columns(['id'])
            ->where(['roleId' => $roleId, 'resourceId' => $resourceId])
            ->limit(1);

        /** @var Rule|null $existing */
        $existing = $this->gateway->selectWith($select)->current();

        if (null === $existing) {
            $this->gateway->insert($data);

            $id = $this->gateway->getLastInsertValue();

            return null !== $id ? (int) $id : false;
        }

        $set = ['type' => $type->value];
        if (null !== $parentResourceId) {
            $set['parentResourceId'] = $parentResourceId;
        }
        if (null !== $assertions) {
            $set['assertions'] = json_encode($assertions);
        }

        $result = $this->gateway->update($set, ['roleId' => $roleId, 'resourceId' => $resourceId]);

        return $result >= 0 ? (int) $existing->id : false;
    }

    /**
     * Update only the type column for a specific (roleId, resourceId) pair.
     *
     * @throws SqlException
     */
    public function updateType(string $roleId, string $resourceId, RuleType $newType): bool
    {
        return $this->gateway->update(
            ['type' => $newType->value],
            ['roleId' => $roleId, 'resourceId' => $resourceId],
        ) > 0;
    }
}
