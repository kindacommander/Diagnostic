<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Runner\Skip;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;
use PHPUnit\Framework\TestCase;

class EnvVariableSkipRegistryTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        \putenv('PHPUNIT_SKIP_HEALTH_CHECKS=');
    }

    /**
     * @test
     */
    public function shouldReturnTrueIfCheckShouldBeSkipped(): void
    {
        \putenv('PHPUNIT_SKIP_HEALTH_CHECKS=foo,bar,,qwerty');

        $registry = new EnvVariableSkipRegistry('PHPUNIT_SKIP_HEALTH_CHECKS');

        $definition = $this->createDefinitionWithKey('bar');

        self::assertTrue($registry->isShouldBeSkipped($definition));
    }

    /**
     * @test
     */
    public function shouldReturnFalseIfCheckShouldNotBeSkipped(): void
    {
        \putenv('PHPUNIT_SKIP_HEALTH_CHECKS=foo,bar,,qwerty');

        $registry = new EnvVariableSkipRegistry('PHPUNIT_SKIP_HEALTH_CHECKS');

        $definition = $this->createDefinitionWithKey('some');

        self::assertFalse($registry->isShouldBeSkipped($definition));
    }

    /**
     * Create the definition with key
     *
     * @param string $key
     *
     * @return CheckDefinitionInterface
     */
    private function createDefinitionWithKey($key): CheckDefinitionInterface
    {
        $definition = $this->createMock(CheckDefinitionInterface::class);

        $definition->expects(self::any())
            ->method('getKey')
            ->willReturn($key);

        return $definition;
    }
}
