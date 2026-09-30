<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Schema;

use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\Json;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Literal;
use Webware\Acl\Console\Ddl\Column\Enum;
use Webware\Core\Role;

/**
 * Builds the ACL database schema.
 *
 * The role chain comes from {@see Role::getRoles()}. Rules are seeded from the
 * providers published under the ACL config key's `rule_seed_providers` entry.
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
