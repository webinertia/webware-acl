<?php

declare(strict_types=1);

namespace Webware\Acl\InputFilter;

use Laminas\Filter;
use Laminas\InputFilter;
use Laminas\InputFilter\Exception\ExceptionInterface;
use Override;
use Webware\Core\InputFilter\SystemMessageTrait;

/**
 * @extends InputFilter\InputFilter<array{id: int|null, roleId: string, parentId: array<string>|null}>
 */
final class RoleDataFilter extends InputFilter\InputFilter
{
    use SystemMessageTrait;

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function init(): void
    {
        $this->add([
            'name'        => 'id',
            'allow_empty' => true,
            'filters'     => [
                ['name' => Filter\ToInt::class],
                ['name' => Filter\ToNull::class],
            ],
        ]);

        $this->add([
            'name'     => 'roleId',
            'required' => true,
            'filters'  => [
                ['name' => Filter\StringTrim::class],
            ],
        ]);

        $this->add([
            'name'     => 'parentId',
            'required' => false,
            'filters'  => [
                ['name' => Filter\StringTrim::class],
                [
                    'name'    => Filter\Callback::class,
                    'options' => [
                        'callback' => static function ($value) {
                            if (is_string($value)) {
                                return [$value];
                            }

                            return $value;
                        },
                    ],
                ],
            ],
        ]);
    }
}
