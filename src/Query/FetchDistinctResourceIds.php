<?php

declare(strict_types=1);

namespace Webware\Acl\Query;

use Webware\MessageBus\Query\QueryInterface;

/**
 * Fetch the distinct set of resourceId values across all rules.
 */
final readonly class FetchDistinctResourceIds implements QueryInterface {}
