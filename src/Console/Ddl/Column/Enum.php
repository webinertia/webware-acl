<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Ddl\Column;

use Override;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Ddl\Column\Column;

use function array_splice;
use function implode;
use function str_replace;

/**
 * MySQL ENUM column — not provided by phpdb core.
 *
 * Belongs upstream in php-db/phpdb-mysql; kept here until an upstream Ddl ENUM exists.
 */
final class Enum extends Column
{
    /** @var list<string> */
    private array $values;

    /**
     * @param list<string> $values
     * @param array<string, mixed> $options
     */
    public function __construct(
        string $name,
        array $values,
        bool $nullable = false,
        string|int|float|bool|Literal|null $default = null,
        array $options = [],
    ) {
        $this->values = $values;

        parent::__construct(
            name    : $name,
            nullable: $nullable,
            default : $default,
            options : $options,
        );
    }

    #[Override]
    public function getExpressionData(): array
    {
        $quoted = [];
        foreach ($this->values as $value) {
            $quoted[] = "'{$value}'";
        }

        $quotedValues = implode(
            separator: ',',
            array    : $quoted,
        );
        $enumType = "ENUM({$quotedValues})";

        $data = parent::getExpressionData();

        $data['spec'] = str_replace(
            search : '%s %s',
            replace: "%s {$enumType}",
            subject: $data['spec'],
        );
        array_splice(
            array : $data['values'],
            offset: 1,
            length: 1,
        );

        return $data;
    }
}
