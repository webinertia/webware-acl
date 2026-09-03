<?php

declare(strict_types=1);

/**
 * This file is part of the Webware\Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Acl\InputFilter;

use Laminas\Filter;
use Laminas\InputFilter;
use Laminas\InputFilter\Exception\ExceptionInterface;
use Override;

/**
 * Minimal filter for rule deletion, which only ever carries the role and
 * resource identifiers from the route. The full {@see RuleDataFilter} requires
 * `type`, so it cannot be shared with the DELETE flow under the stateless
 * `validate()` API (which validates every configured input).
 *
 * @extends InputFilter\InputFilter<array{roleId: string, resourceId: string}>
 */
final class RuleDeleteFilter extends InputFilter\InputFilter
{
    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function init(): void
    {
        $this->add([
            'name'     => 'roleId',
            'required' => true,
            'filters'  => [
                ['name' => Filter\StringTrim::class],
            ],
        ]);

        $this->add([
            'name'     => 'resourceId',
            'required' => true,
            'filters'  => [
                ['name' => Filter\StringTrim::class],
            ],
        ]);
    }
}
