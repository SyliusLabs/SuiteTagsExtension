<?php

declare(strict_types=1);

namespace SyliusLabs\SuiteTagsExtension\Suite;

use Behat\Testwork\Suite\SuiteRepository;

interface MutableSuiteRepositoryInterface extends SuiteRepository
{
    /** @return array<string, string[]> */
    public function getSuitesConfigurations(): array;

    public function removeSuiteConfiguration(string $name): void;
}
