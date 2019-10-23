<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Util\VersionComparator;

use Composer\Semver\Semver;

/**
 * Semver version comparator.
 */
class SemverVersionComparator implements VersionComparatorInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!\class_exists(Semver::class)) {
            $message = 'Cannot use SemverVersionComparator. The package "composer/semver" not installed.';

            throw new \RuntimeException($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function satisfies(string $version, string $expectedVersion): bool
    {
        return Semver::satisfies($version, $expectedVersion);
    }
}
