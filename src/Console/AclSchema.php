<?php

declare(strict_types=1);

namespace Webware\Acl\Console;

use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\Json;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Literal;
use Webware\Acl\Console\Ddl\Column\Enum;
use Webware\Acl\Container\Configuration;
use Webware\Acl\RuleType;
use Webware\Core\AclInterface;

use function rtrim;

/**
 * Builds the ACL database schema and seed data.
 *
 * The base seed covers only the roles and rules webware-acl itself owns:
 * the Guest/Member/Administrator/Developer role chain and the ACL manager
 * resources owned by the Developer role. Host applications (e.g. IMS) layer
 * their own roles and rules on top of this base.
 */
final class AclSchema
{
    /**
     * @return list<DropTable>
     */
    public function dropTables(): array
    {
        return [
            new DropTable(table: 'acl_rule')->ifExists(),
            new DropTable(table: 'acl_role')->ifExists(),
        ];
    }

    /**
     * @return list<array{roleId: string, parentId: string}>
     */
    public function roleSeeds(): array
    {
        return [
            ['roleId' => 'Guest', 'parentId' => '[]'],
            ['roleId' => 'Member', 'parentId' => '["Guest"]'],
            ['roleId' => 'Administrator', 'parentId' => '["Member"]'],
            ['roleId' => AclInterface::DEVELOPER_ROLE_ID, 'parentId' => '["Administrator"]'],
        ];
    }

    public function roleTable(): CreateTable
    {
        $table = new CreateTable(table: 'acl_role')->ifNotExists();

        $table->addColumn(
            new Integer(
                name    : 'id',
                nullable: false,
            )->setOptions(options: ['unsigned' => true, 'autoincrement' => true]),
        );
        $table->addColumn(new Varchar(
            name    : 'roleId',
            length  : 50,
            nullable: false,
        ));
        $table->addColumn(
            new Json(
                name    : 'parentId',
                nullable: true,
                default : null,
            )->setOptions(options: [
                'comment' => 'Array of parent roleId strings, e.g. ["Guest","Member"]',
            ]),
        );
        $table->addConstraint(new PrimaryKey(columns: 'id'));
        $table->addConstraint(new UniqueKey(
            columns: 'roleId',
            name   : 'uq_role_id',
        ));
        $table->setOptions(options: [
            'engine'          => new Literal(literal: 'InnoDB'),
            'default charset' => new Literal(literal: 'utf8mb4'),
            'collate'         => new Literal(literal: 'utf8mb4_0900_ai_ci'),
        ]);

        return $table;
    }

    /**
     * @return list<array{
     *   type: string,
     *   roleId: string,
     *   resourceId: string,
     *   assertions: null,
     *   parentResourceId: string|null
     * }>
     */
    public function ruleSeeds(): array
    {
        $prefix  = Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE;
        $manager = rtrim(
            string    : $prefix,
            characters: '.',
        );

        $seeds = [
            [
                'type'             => RuleType::Allow->value,
                'roleId'           => AclInterface::DEVELOPER_ROLE_ID,
                'resourceId'       => $manager,
                'assertions'       => null,
                'parentResourceId' => null,
            ],
        ];

        foreach ([
            'role.read',
            'role.add.modal',
            'role.edit.modal',
            'role.create',
            'role.update',
            'role.delete',
            'rule.create',
            'rule.update',
            'rule.delete',
            'rule.delete.modal',
        ] as $resource) {
            $seeds[] = [
                'type'             => RuleType::Allow->value,
                'roleId'           => AclInterface::DEVELOPER_ROLE_ID,
                'resourceId'       => $prefix . $resource,
                'assertions'       => null,
                'parentResourceId' => $manager,
            ];
        }

        return $seeds;
    }

    public function ruleTable(): CreateTable
    {
        $table = new CreateTable(table: 'acl_rule')->ifNotExists();

        $table->addColumn(
            new Integer(
                name    : 'id',
                nullable: false,
            )->setOptions(options: ['unsigned' => true, 'autoincrement' => true]),
        );
        $table->addColumn(
            new Enum(
                name    : 'type',
                values  : ['Allow', 'Deny'],
                nullable: false,
                default : 'Allow',
            ),
        );
        $table->addColumn(new Varchar(
            name    : 'roleId',
            length  : 50,
            nullable: false,
        ));
        $table->addColumn(
            new Varchar(
                name    : 'resourceId',
                length  : 255,
                nullable: false,
            )->setOptions(options: ['comment' => 'ACL resource string, e.g. "acl.manager.rule"']),
        );
        $table->addColumn(
            new Json(
                name    : 'assertions',
                nullable: true,
                default : null,
            )->setOptions(options: [
                'comment' => 'Array of assertion alias strings, null means no assertions',
            ]),
        );
        $table->addColumn(
            new Varchar(
                name    : 'parentResourceId',
                length  : 255,
                nullable: true,
            )->setOptions(options: [
                'comment' => 'resourceId of the parent rule; null = explicit rule',
            ]),
        );
        $table->addConstraint(new PrimaryKey(columns: 'id'));
        $table->addConstraint(new UniqueKey(
            columns: ['roleId', 'resourceId'],
            name   : 'uq_rule',
        ));
        $table->setOptions(options: [
            'engine'          => new Literal(literal: 'InnoDB'),
            'default charset' => new Literal(literal: 'utf8mb4'),
            'collate'         => new Literal(literal: 'utf8mb4_0900_ai_ci'),
        ]);

        return $table;
    }
}
