<?php

declare(strict_types=1);

namespace SyliusLabs\SuiteTagsExtension\Suite;

use Behat\Testwork\Suite\Exception\SuiteConfigurationException;
use Behat\Testwork\Suite\Exception\SuiteGenerationException;
use Behat\Testwork\Suite\Generator\SuiteGenerator;
use Behat\Testwork\Suite\Suite;
use Behat\Testwork\Suite\SuiteRegistry;

/** @see SuiteRegistry */
final class MutableSuiteRegistry implements MutableSuiteRepositoryInterface
{
    private bool $suitesGenerated = false;

    /** @var SuiteGenerator[] */
    private array $generators = [];

    /** @var array<string, array{0: string|null, 1: array<string, string[]>}> */
    private array $suiteConfigurations = [];

    /** @var Suite[] */
    private array $suites = [];

    public function registerSuiteGenerator(SuiteGenerator $generator): void
    {
        $this->generators[] = $generator;
        $this->suitesGenerated = false;
    }

    /** @param array<string, string[]> $settings */
    public function registerSuiteConfiguration(string $name, ?string $type, array $settings): void
    {
        if (isset($this->suiteConfigurations[$name])) {
            throw new SuiteConfigurationException(sprintf(
                'Suite configuration for a suite "%s" is already registered.',
                $name,
            ), $name);
        }

        $this->suiteConfigurations[$name] = [$type, $settings];
        $this->suitesGenerated = false;
    }

    /** @return array<string, array{0: string|null, 1: array<string, string[]>}> */
    public function getSuitesConfigurations(): array
    {
        return $this->suiteConfigurations;
    }

    public function removeSuiteConfiguration(string $name): void
    {
        unset($this->suiteConfigurations[$name]);
        $this->suitesGenerated = false;
    }

    /** @return Suite[] */
    public function getSuites(): array
    {
        if ($this->suitesGenerated) {
            return $this->suites;
        }

        $this->suites = [];
        foreach ($this->suiteConfigurations as $name => $configuration) {
            [$type, $settings] = $configuration;

            $this->suites[] = $this->generateSuite($name, $type, $settings);
        }

        $this->suitesGenerated = true;

        return $this->suites;
    }

    /**
     * @param array<string, string[]> $settings
     *
     * @throws SuiteGenerationException
     */
    private function generateSuite(string $name, ?string $type, array $settings): Suite
    {
        foreach ($this->generators as $generator) {
            if (!$generator->supportsTypeAndSettings($type, $settings)) {
                continue;
            }

            return $generator->generateSuite($name, $settings);
        }

        throw new SuiteGenerationException(sprintf(
            'Can not find suite generator for a suite `%s` of type `%s`.',
            $name,
            $type,
        ), $name);
    }
}
