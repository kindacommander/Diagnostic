<?php

/*
 * This file is part of the FiveLab Diagnostic package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FiveLab\Component\Diagnostic\Check\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Result;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;

/**
 * Check MySQL version.
 */
class DbalMysqlVersionCheck extends AbstractDbalCheck
{
    public const MYSQL_EXTRACT_VERSION_REGEX = '/^([\d\.]+)/';

    /**
     * @var string
     */
    private string $expectedVersion;

    /**
     * @var VersionComparatorInterface
     */
    private VersionComparatorInterface $versionComparator;

    /**
     * @var string
     */
    private string $actualVersion = 'unknown';

    /**
     * Constructor.
     *
     * @param DriverConnection|Connection     $connection
     * @param string                          $expectedVersion
     * @param VersionComparatorInterface|null $versionComparator
     *
     * @see https://getcomposer.org/doc/articles/versions.md
     */
    public function __construct(object $connection, string $expectedVersion, VersionComparatorInterface $versionComparator = null)
    {
        parent::__construct($connection);

        $this->connection = $connection;
        $this->expectedVersion = $expectedVersion;
        $this->versionComparator = $versionComparator ?: new SemverVersionComparator();
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        try {
            $query = "SHOW VARIABLES WHERE Variable_name = 'version'";
            $statement = $this->connection->executeQuery($query);

            [, $mysqlVersionVariableContent] = $statement->fetchNumeric();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Failed checking MySQL version: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        $this->actualVersion = $this->extractMysqlServerDistributedVersion($mysqlVersionVariableContent);

        if (!$this->versionComparator->satisfies($this->actualVersion, $this->expectedVersion)) {
            return new Failure(\sprintf(
                'Expected MySQL server of version "%s", found "%s".',
                $this->expectedVersion,
                $this->actualVersion
            ));
        }

        return new Success('MySQL version matches an expected one.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $parameters = parent::getExtraParameters();

        $parameters['actualVersion'] = $this->actualVersion;
        $parameters['expectedVersion'] = $this->expectedVersion;

        return $parameters;
    }

    /**
     * @param string $buildVersion
     *
     * @return string
     */
    private function extractMysqlServerDistributedVersion(string $buildVersion): string
    {
        $matches = [];
        \preg_match(self::MYSQL_EXTRACT_VERSION_REGEX, $buildVersion, $matches);

        return \rtrim($matches[0], '.');
    }
}
