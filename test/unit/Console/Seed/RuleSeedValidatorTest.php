<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Seed;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Console\Seed\RuleSeedValidator;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleType;

#[CoversClass(RuleSeedValidator::class)]
#[CoversMethod(RuleSeedValidator::class, 'violations')]
final class RuleSeedValidatorTest extends TestCase
{
    /** @var list<string> */
    private const array ROUTES = ['admin.acl', 'admin.acl.role.read', 'user.session.read'];

    private RuleSeedValidator $validator;

    #[Test]
    public function acceptsAnAnchorThatIsARoute(): void
    {
        $violations = $this->validator->violations(
            ruleSeeds : [$this->seed(resourceId: 'admin.acl')],
            routeNames: self::ROUTES,
        );

        self::assertSame([], $violations);
    }

    #[Test]
    public function acceptsAnchorsRoutesAndTheirChildren(): void
    {
        $violations = $this->validator->violations(
            ruleSeeds : [
                $this->seed(resourceId: 'user'),
                $this->seed(
                    resourceId      : 'user.session.read',
                    parentResourceId: 'user',
                ),
                $this->seed(
                    roleId    : 'Developer',
                    resourceId: 'admin.acl',
                ),
                $this->seed(
                    roleId          : 'Developer',
                    resourceId      : 'admin.acl.role.read',
                    parentResourceId: 'admin.acl',
                ),
            ],
            routeNames: self::ROUTES,
        );

        self::assertSame([], $violations);
    }

    #[Test]
    public function acceptsTheSamePairClaimedWithIdenticalValues(): void
    {
        $violations = $this->validator->violations(
            ruleSeeds : [
                $this->seed(resourceId: 'user'),
                $this->seed(
                    resourceId      : 'user.session.read',
                    parentResourceId: 'user',
                ),
                $this->seed(
                    resourceId      : 'user.session.read',
                    parentResourceId: 'user',
                ),
            ],
            routeNames: self::ROUTES,
        );

        self::assertSame([], $violations);
    }

    #[Test]
    public function rejectsAnAnchorNothingHangsFrom(): void
    {
        $violations = $this->validator->violations(
            ruleSeeds : [$this->seed(resourceId: 'user.hidden')],
            routeNames: self::ROUTES,
        );

        self::assertSame(
            ['Anchor node "user.hidden" is neither a registered route name nor the parent of any seed.'],
            $violations,
        );
    }

    #[Test]
    public function rejectsAParentThatResolvesToNothing(): void
    {
        $violations = $this->validator->violations(
            ruleSeeds : [$this->seed(
                resourceId      : 'admin.acl.role.read',
                parentResourceId: 'admin.acl.role',
            )],
            routeNames: self::ROUTES,
        );

        self::assertSame(
            [
                'Parent "admin.acl.role" of resource "admin.acl.role.read" is neither a registered route name nor an anchor node.',
            ],
            $violations,
        );
    }

    #[Test]
    public function rejectsAResourceThatAnswersToNoRoute(): void
    {
        $violations = $this->validator->violations(
            ruleSeeds : [
                $this->seed(resourceId: 'user'),
                $this->seed(
                    resourceId      : 'user.manager.session.read',
                    parentResourceId: 'user',
                ),
            ],
            routeNames: self::ROUTES,
        );

        self::assertSame(
            ['Resource "user.manager.session.read" is not a registered route name.'],
            $violations,
        );
    }

    #[Test]
    public function rejectsASelfParentedResource(): void
    {
        $violations = $this->validator->violations(
            ruleSeeds : [$this->seed(
                resourceId      : 'admin.acl.role.read',
                parentResourceId: 'admin.acl.role.read',
            )],
            routeNames: self::ROUTES,
        );

        self::assertSame(
            ['Resource "admin.acl.role.read" names itself as its parent.'],
            $violations,
        );
    }

    #[Test]
    public function rejectsBothProblemsOnTheSameRow(): void
    {
        $violations = $this->validator->violations(
            ruleSeeds : [$this->seed(
                resourceId      : 'nope.read',
                parentResourceId: 'nope',
            )],
            routeNames: self::ROUTES,
        );

        self::assertSame(
            [
                'Resource "nope.read" is not a registered route name.',
                'Parent "nope" of resource "nope.read" is neither a registered route name nor an anchor node.',
            ],
            $violations,
        );
    }

    #[Test]
    public function rejectsTheSamePairClaimedWithDifferentValues(): void
    {
        $violations = $this->validator->violations(
            ruleSeeds : [
                $this->seed(resourceId: 'user'),
                $this->seed(
                    resourceId      : 'user.session.read',
                    parentResourceId: 'user',
                ),
                $this->seed(
                    type            : RuleType::Deny,
                    resourceId      : 'user.session.read',
                    parentResourceId: 'user',
                ),
            ],
            routeNames: self::ROUTES,
        );

        self::assertSame(
            ['Role "Guest" and resource "user.session.read" are claimed twice with different values.'],
            $violations,
        );
    }

    protected function setUp(): void
    {
        $this->validator = new RuleSeedValidator();
    }

    private function seed(
        RuleType $type = RuleType::Allow,
        string $roleId = 'Guest',
        string $resourceId = 'user.session.read',
        ?string $parentResourceId = null,
    ): RuleSeed {
        return new RuleSeed(
            type            : $type,
            roleId          : $roleId,
            resourceId      : $resourceId,
            parentResourceId: $parentResourceId,
        );
    }
}
