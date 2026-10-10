<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Seed;

/**
 * Outcome of a seeding run.
 *
 * `$tableMissing` is reported rather than thrown: the table belongs to the
 * package that owns the DDL, and a seeding command must be able to warn and
 * carry on when it runs before that package's schema exists.
 *
 * `$violations` are integrity problems found *before* anything was written -
 * a resource that answers to no registered route, a parent that resolves to
 * nothing, two providers claiming the same role and resource with different
 * values. Each of these is fatal later, at ACL load time, when the information
 * needed to explain it is no longer available.
 *
 * @api
 */
final readonly class SeedResult
{
    /**
     * @param list<string> $violations
     */
    public function __construct(
        public int $seeded = 0,
        public bool $tableMissing = false,
        public array $violations = [],
    ) {}

    public function hasViolations(): bool
    {
        return [] !== $this->violations;
    }
}
