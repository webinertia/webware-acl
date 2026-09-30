<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Seed;

use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleSeedProviderInterface;

/**
 * Gathers the seeds every published provider contributes.
 *
 * @internal
 */
final readonly class RuleSeedCollector
{
    /**
     * @param list<RuleSeedProviderInterface> $providers
     */
    public function __construct(
        private array $providers,
        private string $adminName,
    ) {}

    /**
     * @return list<RuleSeed>
     */
    public function collect(): array
    {
        $seeds = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->ruleSeeds($this->adminName) as $seed) {
                $seeds[] = $seed;
            }
        }

        return $seeds;
    }
}
