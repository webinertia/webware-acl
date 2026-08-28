<?php

declare(strict_types=1);

namespace Webware\Acl\Query;

use Webware\MessageBus\Query\QueryInterface;

/**
 * Fetch all rules as plain arrays with decoded assertions.
 */
final readonly class FetchAllRules implements QueryInterface {}
